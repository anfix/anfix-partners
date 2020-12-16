<?php

/*
* 2006-2015 Lucid Networks
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL => 
* http => //opensource.org/licenses/afl-3.0.php
*
* DISCLAIMER
*
*  Date =>  9/2/16 18 => 57
*  @author Lucid Networks <info@lucidnetworks.es>
*  @copyright  2006-2015 Lucid Networks
*  @license    http => //opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

/**
 * Crea un nuevo presupuesto
 */
  
include 'example_utils.php';  

$companyId = firstCompanyId(); //Obtención del id de la primera empresa disponible (función únicamente válida para ejemplos)

$budget = Anfix\CustomerBudget::create([
          "CustomerBudgetSerialNum" =>  "P2015",
          "CustomerBudgetDate" =>  "17/02/2015",
          "CustomerBudgetCustomerName" =>  "Otro cliente",
          "CustomerBudgetCustomerIdentificationNumber" =>  "11111111H",
          "CustomerBudgetId" =>  null,
          "Action" =>  "ADD",
          "CustomerBudgetStateId" =>  "1",
          "CustomerBudgetCustomerTaxTypeId" =>  "1",
          "CustomerBudgetCustomerVATTypeId" =>  "1",
          "CustomerBudgetCustomerIdentificationTypeId" =>  "1",
          "CustomerBudgetCustomerId" =>  null,
          "CustomerBudgetTemplateId" =>  "65",
          "CustomerBudgetTemplateLanguage" =>  1,
          "Address" =>  [
              (object)[ //Tal y como se indica en la documentación de Anfix mediante {} cada una de las direcciones debe ser un objeto
                    "AddressText" =>  "Otra dirección",
                    "AddressPostalCode" =>  "47005",
                    "AddressProvinceId" =>  "L",
                    "AddressCountryId" =>  "1",
                    "Action" =>  "ADD",
                    "AddressId" =>  null
                ]
          ],
          "CustomerBudgetLine" =>  [
              (object)[ //Tal y como se indica en la documentación de Anfix mediante {} cada una de las direcciones debe ser un objeto
                    "CustomerBudgetLineQuantity" =>  4,
                    "CustomerBudgetLineItemRef" =>  "1",
                    "CustomerBudgetLineItemDescription" =>  "Cableado",
                    "CustomerBudgetLinePriceisDirty" =>  true,
                    "CustomerBudgetLinePrice" =>  25,
                    "Action" =>  "ADD"
              ]
          ]
    ],$companyId);

print_result('Creación de un nuevo presupuesto',$budget->getArray());

