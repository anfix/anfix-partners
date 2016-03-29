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

namespace Anfix;

class CompanyAccountingAccount extends BaseModel
{
    protected $applicationId = '3';
    protected $apiUrlSufix = 'company/accountingplan/accountingaccountmanager/';
    protected $update = true;
    protected $create = true;
    protected $delete = true;
	
   /**
	* Impresión de cuentas del plan contable de la empresa.
	* Su utilización es similar a ->get, siempre después de ::where()
	* @param array $params AccountingPeriodYear obligatorio
	* @param array $fields = [] Campos a devolver
	* @param string $order Ordenación 	CompanyAccountingAccountDescription, CompanyAccountingAccountNumber
	* @return array BaseModel
	*/
	public function accountPrint(array $params, array $fields = [], $order = 'CompanyAccountingAccountDescription'){
        $params['Order'] = $order;
		return $this->get($fields,null,'print',$params);
	}
	
	/**
     * Selección de datos de subcuentas contables.
	 * @param string $accountingPeriodYear Año
	 * @param string $companyAccountingAccountNumber Número de cuenta a seleccionar.
     * @param string $companyId Identificador de la empresa
     * @return Object
     */
    public static function select($accountingPeriodYear, $companyAccountingAccountNumber, $companyId){
        $obj = new static([],false,$companyId);
		$params = ['AccountingPeriodYear' => $accountingPeriodYear, 'CompanyAccountingAccountNumber' => $companyAccountingAccountNumber];
        $result = self::_send($params,$companyId,'select');

        return $result->outputData->{$obj->Model};
    }

}