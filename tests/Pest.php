<?php

use Tests\TestCaseData;
use Tests\TestCaseNoData;

uses(TestCaseData::class)
    ->in(sprintf('%s/%s', __DIR__, '/Data'));

uses(TestCaseNoData::class)
    ->in(sprintf('%s/%s', __DIR__, '/NoData'));
