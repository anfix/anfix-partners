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
*  @author Lucid Networks <info@lucidnetworks.es>
*  @copyright  2006-2015 Lucid Networks
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

namespace Anfix;

class Expiration extends BaseModel
{
    protected $applicationId = 'e';
    protected $update = false;
    protected $create = false;
    protected $delete = false;

    /**
     * Cálculo de vencimientos según una forma de cobro.
     * @param $params array de parámetros, ExpirationsPayChargeMethodId, ExpirationsQuantity y ExpirationsStartDate obligatorios
     * @param $companyId
     * @return Object
     */
    public static function compute(array $params,$companyId){
        $obj = new static([],false,$companyId);
		$urlBase = str_replace('/cm/','/',$obj->apiBaseUrl);
        $result = self::_send($params,$companyId,'compute',[],[],$urlBase);
        return $result->outputData->{$obj->Model};
    }
}