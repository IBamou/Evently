<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

uses(BaseTestCase::class, RefreshDatabase::class)->in('Feature');
