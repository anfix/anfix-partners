<?php

/*
* 2006-2015 Lucid Networks
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
* DISCLAIMER
*
*  Date: 9/2/16 18:57
*  @author Networkkings <info@lucidnetworks.es>
*  @copyright  2006-2015 Lucid Networks
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

/**
 * Métodos especiales de la entidad
 * Los métodos search, create, update y delete son similares a todas las demás entidades
 */
  
include 'example_utils.php';  

$companyId = firstCompanyId(); //Obtención del id de la primera empresa disponible (función únicamente válida para ejemplos)

//Selección de datos de subcuentas contables.
$data = Anfix\CompanyAccountingAccount::select('2016', '7000000', $companyId);
print_result('Selección de datos de subcuentas contables',$data);

//Impresión de cuentas del plan contable de la empresa.
$data = Anfix\CompanyAccountingAccount::where([], $companyId)->accountPrint(['AccountingPeriodYear' => '2016']);
print_result('Selección de datos de subcuentas contables',$data);