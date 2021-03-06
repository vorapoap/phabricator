<?php

final class NuanceItemQuery
  extends NuanceQuery {

  private $ids;
  private $phids;
  private $sourcePHIDs;
  private $itemTypes;
  private $itemKeys;
  private $containerKeys;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withSourcePHIDs(array $source_phids) {
    $this->sourcePHIDs = $source_phids;
    return $this;
  }

  public function withItemTypes(array $item_types) {
    $this->itemTypes = $item_types;
    return $this;
  }

  public function withItemKeys(array $item_keys) {
    $this->itemKeys = $item_keys;
    return $this;
  }

  public function withItemContainerKeys(array $container_keys) {
    $this->containerKeys = $container_keys;
    return $this;
  }

  public function newResultObject() {
    return new NuanceItem();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function willFilterPage(array $items) {
    $source_phids = mpull($items, 'getSourcePHID');

    // NOTE: We always load sources, even if the viewer can't formally see
    // them. If they can see the item, they're allowed to be aware of the
    // source in some sense.
    $sources = id(new NuanceSourceQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($source_phids)
      ->execute();
    $sources = mpull($sources, null, 'getPHID');

    foreach ($items as $key => $item) {
      $source = idx($sources, $item->getSourcePHID());
      if (!$source) {
        $this->didRejectResult($items[$key]);
        unset($items[$key]);
        continue;
      }
      $item->attachSource($source);
    }

    $type_map = NuanceItemType::getAllItemTypes();
    foreach ($items as $key => $item) {
      $type = idx($type_map, $item->getItemType());
      if (!$type) {
        $this->didRejectResult($items[$key]);
        unset($items[$key]);
        continue;
      }
      $item->attachImplementation($type);
    }

    return $items;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->sourcePHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'sourcePHID IN (%Ls)',
        $this->sourcePHIDs);
    }

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'phid IN (%Ls)',
        $this->phids);
    }

    if ($this->itemTypes !== null) {
      $where[] = qsprintf(
        $conn,
        'itemType IN (%Ls)',
        $this->itemTypes);
    }

    if ($this->itemKeys !== null) {
      $where[] = qsprintf(
        $conn,
        'itemKey IN (%Ls)',
        $this->itemKeys);
    }

    if ($this->containerKeys !== null) {
      $where[] = qsprintf(
        $conn,
        'itemContainerKey IN (%Ls)',
        $this->containerKeys);
    }

    return $where;
  }

}
