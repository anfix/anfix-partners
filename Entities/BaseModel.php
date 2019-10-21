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
*  Date: 9/2/16 18:21
*  @author Lucid Networks <info@lucidnetworks.es>
*  @copyright  2006-2015 Lucid Networks
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

namespace Anfix;

use Anfix\Exceptions\AnfixException;
use Anfix\Exceptions\AnfixResponseException;

/**
 * Class BaseModel
 * Esta clase es la clase base desde la que deben heredar todos los modelos de anfix
 * su comportamiento en muy similar al de Eloquent, salvo en la intrucción where que no admite llamadas recursivas
 * debe rellenar en cada modelo los datos obligatorios para poder establecer una conexión con anfix
 * @package Lucid\Anfix
 */
class BaseModel
{
    /** @var  string Obligatorio, Identificador de la App Anfix, este identificador asocia la Url base por defecto conforme a config/anfix.php */
    protected $applicationId;
    /**  @var string Opcional, Nombre de la entidad en Anfix, por defecto será el nombre de la clase */
    protected $Model;
    /**  @var string Opcional, Nombre de la clave primaria en Anfix, por defecto {$Model}Id */
    protected $primaryKey;
    /**  @var string Opcional, Url de la API a la que conectar, por defecto se obtiene de config/anfix en función del applicationId */
    protected $apiBaseUrl;
    /**  @var string Opcional, Sufijo que se añade a la url de la API, por defecto nombre de la entidad, si se indica apiBaseUrl no se tendrá en cuenta este parámetro */
    protected $apiUrlSufix;
    /**  @var bool Por defecto se permite la actualización */
    protected $update = true;
    /**  @var bool Por defecto no se permite la creación */
    protected $create = false;
    /**  @var bool Por defecto no se permite el borrado */
    protected $delete = false;

    /** @var  array Configuración */
    protected $config;
    /** @var  array Token y Secret */
    protected $token;
    /** @var string Identificador empresa */
    protected $companyId = null;
    /** @var array Borrados de datos que se enviarán */
    private $draft = [];
    /** @var array Contiene las propiedades anfix del objeto */
    private $attributes = [];
    /** @var array Condiciones de filtrado para el siguiente search */
    private $wherecond = [];
    /** @var array ApplicationsId que requieren definición de la compañia para trabajar */
    private $applicationsIdCompanyMandatory = ['E','3'];

    /**
     * BaseModel constructor.
     * @param array $params
     * @param bool $emptyDraft
     * @param null $companyId
     * @throws AnfixException
     */
    public function __construct(array $params = [], $emptyDraft = false, $companyId = null){

        if(empty($this->applicationId))
            throw new AnfixException('Debe indicar un applicationId en el modelo para poder utilizar la API');

        if(!$this->Model) {
            $class = explode('\\', get_called_class());
            $this->Model = end($class);
        }

        $this->config = Anfix::getEnv()['config'];
        $this->token = Anfix::getEnv()['token'];

        if(!empty($params))
            $this->fill($params);

        if(isset($params['wherecond']))
            $this->wherecond = $params['wherecond'];

        if($emptyDraft)
            $this->emptyDraft();
            
        if(empty($this->apiUrlSufix))
            $this->apiUrlSufix = strtolower($this->Model).'/';

        if(empty($this->apiBaseUrl))
            $this->apiBaseUrl = $this->config['applicationIdUrl'][$this->applicationId].$this->apiUrlSufix;

        if(empty($this->primaryKey))
            $this->primaryKey = $this->Model.'Id';

        if(!empty($companyId))
            $this->companyId = $companyId;
        else if(in_array($this->applicationId,$this->applicationsIdCompanyMandatory))
            throw new AnfixException('Esta entidad requiere companyId');

        return $this;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
        if($value !== null)
            $this->draft[$name] = $value;
        else
            unset($this->draft[$name]);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name){
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * Devuelve todas las propiedades del objeto en forma de array
     * @return array
     */
    public function getArray(){
        return $this->attributes;
    }

    /**
     * Rellena el objeto con los parámetros dados
     * @param array $params
     * @return static
     */
    public function fill(array $params){
        foreach($params as $k => $value) {
            if($k == 'wherecond')
                continue;

            $this->$k = $value;
        }
        return $this;
    }

    /**
     * Elimina el draft
     */
    public function emptyDraft(){
        $this->draft = [];
    }

    /**
     * Crea o actualiza el objeto Anfix
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public function save(){
        if(empty($this->draft))
            return $this;

        if(empty($this->attributes[$this->primaryKey])) {
            $return = $this->create($this->draft, $this->companyId);
            $this->fill(get_object_vars($return));
            $this->emptyDraft();
            return $return;
        }else
            return $this->update($this->draft);
    }

    /**
     * Elimina un objeto de la API
     * @param $params = [] Parámetros especiales
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return bool
     */
    public function delete($params = []){
        if(!$this->delete)
            throw new AnfixException("El objeto {$this->Model} no admite eliminación");

        if(empty($this->attributes[$this->primaryKey]))
            throw new AnfixException('No puede borrar un objeto sin id');

        return $this->destroy($this->attributes[$this->primaryKey], $this->companyId, 'delete',$params);
    }

    /**
     * Devuelve un array de objetos desde la API
     * @param array $fields = [] Campos a devolver
     * @param int $maxRows = null Máximo de filas a mostrar, si no se indica se devolverán 50
     * @param null $minRowNumber Primera entrada a devolver como resultado del conjunto total de entradas devueltas por la operación
     * @param array $order Lista con los campos por los que se quiere ordenar los resultados
     * @param string $orderTypes ”ASC” o ”DESC”
     * @param string $path = 'search' Path de la función en anfix
     * @param array $params = [] Parámetros especiales a añadir en la solicitud
     * @param string $apiUrl = null Indica una url base diferente a la del modelo para casos especiales
     * @return array
     */
    public function get(array $fields = [], $maxRows = null, $minRowNumber = null, array $order = [], $orderTypes = 'ASC', $path = 'search', array $params = [], $apiUrl = null){
        $obj_data = $params;
        $return = [];

        if(!empty($fields))
            $obj_data['Fields'] = $fields;

        if(!empty($this->wherecond))
            $obj_data['Filters'] = $this->wherecond;

        if(!empty($maxRows))
            $obj_data['MaxRows'] = $maxRows;

        if(!empty($minRowNumber))
            $obj_data['MinRowNumber'] = $minRowNumber;

        if(!empty($order)) {
            $obj_data['Order'] = $order;
            $obj_data['OrderTypes'] = $orderTypes;
        }

        $result = self::_send($obj_data,$this->companyId,$path,$this->config,$this->token,$apiUrl);

        if(!isset($result->outputData->TotalRowNumber) && is_object($result->outputData->{$this->Model}))
            return new $this(get_object_vars($result->outputData->{$this->Model}), true, $this->companyId);

        if($result->outputData->TotalRowNumber == 0)
            return [];

        foreach($result->outputData->{$this->Model} as $params){
            $obj = new $this(get_object_vars($params), true, $this->companyId);	
	    if(!empty($params->{$this->primaryKey}))			
            	$return[$params->{$this->primaryKey}] = $obj;
            else
            	$return[] = $obj;
        }    

        return $return;
    }

    /**
     * Actualiza el Modelo en la API
     * @param array $params
     * @return BaseModel
     * @param string $path = 'update' Path de la función en anfix
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public function update(array $params, $path = 'update'){
        if(!$this->update)
            throw new AnfixException("El objeto {$this->Model} no admite actualización");

        if(empty($this->attributes[$this->primaryKey]))
            throw new AnfixException("No se puede realizar un update si el objeto no tiene id");

        $params[$this->primaryKey] = $this->attributes[$this->primaryKey];

        $result = self::_send($params,$this->companyId,$path,$this->config,$this->token);

        if($result->result == 0) {
            $this->fill($params);
            $this->draft = [];
        }

        return $this;
    }

    // ############################## STATIC FUNCTIONS ##############################

    /**
     * Crea el objeto en la API
     * @param array $params
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @param string $path = 'create' Path de la función en anfix
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public static function create(array $params, $companyId = null, $path = 'create'){
        $obj = new static([],false,$companyId);
        if(!$obj->create)
            throw new AnfixException("El objeto {$obj->Model} no admite creación");

        $result = self::_send($params,$companyId,$path);
        if(empty($result->outputData->{$obj->Model}))
            return $result->result;

        $received_params = array_filter(get_object_vars($result->outputData->{$obj->Model}),function($value){ return !is_array($value); }); //Parametros no array
        $received_params_arr = array_filter(get_object_vars($result->outputData->{$obj->Model}),function($value){ return is_array($value); }); //Parámetros de tipo array

        //Mezclamos aquellos valores que no son de tipo array
        $params = array_merge($params,$received_params);

        //Mezclamos los valores de tipo array manualmente
        foreach($received_params_arr as $k => $arr){
            foreach($arr as $pos => $o){
                if(!empty($params[$k][$pos])){
                    if(is_array($params[$k][$pos]) && is_object($o)) //If origin is an array and dest an object
                        $params[$k][$pos] = array_replace_recursive($params[$k][$pos], get_object_vars($o));
                    else if (is_object($params[$k][$pos]) && is_object($o)) //If origin and dest are objects
                        $params[$k][$pos] = (object)array_replace_recursive(get_object_vars($params[$k][$pos]), get_object_vars($o));
                }
                else //If origin doesn't exists
                    $params[$k][$pos] = $o;
            }
        }

        return new static($params,true,$companyId);
    }

    /**
     * Destruye un objeto por su id
     * @param string $id
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @param string $path = 'delete' Path de la función en anfix
     * @param $params = [] Parámetros especiales
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return bool
     */
    public static function destroy($id, $companyId = null, $path = 'delete', $params = []){
        $obj = new static([],false,$companyId);

        if(!$obj->delete)
            throw new AnfixException("El objeto {$obj->Model} no admite eliminación");

        $params[$obj->primaryKey] = $id;

        $result = self::_send($params,$companyId,$path);

        if($result->result == 0)
            return true;

        return false;
    }

    /**
     * Inserta una condición where para la obtención de datos
     * @param array $conditions
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @return static
     */
    public static function where(array $conditions, $companyId = null){
        $wherecond = [];
        foreach($conditions as $k => $val)
            $wherecond[] = [$k => $val];

        return new static(['wherecond' => $wherecond], false, $companyId);
    }

    /**
     * Devuelve un objeto por su id o null si no existe
     * @param $id
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public static function find($id, $companyId = null){
        $obj = new static([],false,$companyId);
        return static::first([$obj->primaryKey => $id], $companyId);
    }

    /**
     * Devuelve un objeto por su id o una excepción si no existe
     * @param $id
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @return static
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     */
    public static function findOrFail($id, $companyId = null){
        $obj = static::find($id, $companyId);
        if(empty($obj))
            throw new AnfixException("No se ha encontrado ningún objeto con el id {$id}");

        return $obj;
    }

    /**
     * Devuelve todos los elementos de la colección
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return array static
     */
    public static function all($companyId = null){
        $obj = new static([], false, $companyId);
        return $obj->get();
    }

    /**
     * Realiza la consulta y devuelve el primer elemento
     * @param array $params
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public static function first(array $params, $companyId = null){
        $data = static::where($params, $companyId)->get([], 1, null, [], 'ASC');
        if(empty($data))
            return null;

        return reset($data);
    }

    /**
     * Devuelve el primer elemento de la colección o un nuevo objeto
     * @param array $params
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public static function firstOrNew(array $params, $companyId = null){
        $obj = static::first($params, $companyId);
        if(!empty($obj))
            return $obj;

        return new static($params, false, $companyId);
    }

    /**
     * Devuelve el primer elemento de la colección o crea uno nuevo si no existe
     * @param array $params
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public static function firstOrCreate(array $params, $companyId = null){
        $obj = static::first($params, $companyId);
        if(!empty($obj))
            return $obj;

        return static::create($params, $companyId);
    }

    /**
     * Devuelve el primer elemento de la colección o genera una Excepción
     * @param array $params
     * @param string $companyId Identificador de la compañia, sólo necesario en algunas entidades
     * @throws Exceptions\AnfixException
     * @throws Exceptions\AnfixResponseException
     * @return static
     */
    public static function firstOrFail(array $params, $companyId = null){
        $obj = static::first($params, $companyId);
        if($obj)
            return $obj;
	
	if (isset($obj->Model)) {
            $objName = $obj->Model;
        } else {
            $objName = get_called_class();
        }
        throw new AnfixException("No se ha encontrado ningún objeto {$objName} con los parámetros indicados");
    }
    
   /**
	* Descarga un fichero
	* @param array $params Parámetros para la descarga
    * @params string $path Ruta donde se guardará el fichero descargado
	* @params string $url Url punto acceso, por defecto {$apiBaseUrl}/download
	* @return true
	*/
	public static function _download(array $params, $path, $url = null){
		$obj = new static($params);
        
		if(empty($url))
			$url = $obj->apiBaseUrl.'download';

        return Anfix::getFile($url,$params,$path);
	}

   /**
    * Envia una solicitud estándar
    * @param array $params
    * @param null $companyId
    * @param string $path Path endpoint
    * @param array $config Parámetros de configuración o config por defecto si vacío
    * @param array $token ['TOKEN','TOKEN_PASSWORD'] o default_token si vacío
    * @param string $baseUrl = null Indica una url base diferente a la del modelo para casos especiales
    * @return mixed
    */
    protected static function _send(array $params, $companyId = null, $path, array $config = [], array $token = [], $baseUrl = null){
        $obj = new static([],false,$companyId);

        if(empty($baseUrl))
            $baseUrl = $obj->apiBaseUrl;

        return Anfix::sendRequest($baseUrl.$path,[
            'applicationId' =>  $obj->applicationId,
            'companyId' => $companyId,
            'inputBusinessData' => [
                $obj->Model => !empty($params) ? $params : new \stdClass()
            ]
        ],$config,$token);
    }

}
