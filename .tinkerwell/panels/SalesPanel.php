<?php

use Tinkerwell\Panels\Panel;
use Tinkerwell\Panels\Table\Section;
use Tinkerwell\Panels\Table\Table;

class SalesPanel extends Panel
{
  public function __construct()
  {
    $this->setTitle("Sales");

    $sales = Table::make()->addSection(
      Section::make()
        ->setTitle("Section 1")
        ->addRow("Row 1", 0)
        ->addRow("Row 2", 1000)
    );

    $this->setContent($sales);
  }
}
