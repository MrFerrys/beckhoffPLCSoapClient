<?php 
namespace mrferrys\beckhoffplcsoapclient;

define("VAR_NAME_OFFSET"         ,30);
define("VAR_OFFSET_OFFSET"       ,8);
define("VAR_INDEX_GROUP_OFFSET"  ,4);
define("VAR_SIZE_OFFSET"         ,12);

/**
 * BeckHoff PLC Handler.
 * 
 * 
 * @author mrferrys
 */
/**
 *
        PACK:
        a 	cadena rellena de NUL
        A 	cadena rellena de SPACE
        h 	cadena hexadecimal, nibble bajo primero
        H 	cadena hexadecimal, nibble alto primero
        c	signed char
        C 	carácter sin signo
        s 	short con signo (siempre 16 bits, orden de byte de la máquina)
        S 	short sin signo (siempre 16 bits, orden de byte de la máquina)
        n 	short sin signo (siempre 16 bits, orden de byte big endian)
        v 	short sin signo (siempre 16 bits, orden de byte little endian)
        i 	integer con signo (tamaño y orden de byte dependientes de la máquina)
        I 	integer sin signo (tamaño y orden de byte dependientes de la máquina)
        l 	long con signo (siempre 32 bits, orden de byte de la máquina)
        L 	long sin signo (siempre 32 bits, orden de byte de la máquina)
        N 	long sin signo (siempre 32 bits, orden de byte big endian)
        V 	long sin signo (siempre 32 bits, orden de byte little endian)
        q 	long largo con signo (siempre 64 bit, orden de byte big endian)
        Q 	long largo sin signo (siempre 64 bit, orden de byte big endian)
        J 	long largo sin signo (siempre 64 bit, orden de byte big endian)
        P 	long largo sin signo (siempre 64 bit, orden de byte little endian)
        f 	float (tamaño y representación dependientes de la máquina)
        d 	double (tamaño y representación dependientes de la máquina)
        x 	byte NUL
        X 	copia de seguridad de un byte
        Z 	Cadena rellena de NUL (nuevo en PHP 5.5)
        @ 	relleno de NUL hasta la posición absoluta
 * 
 * @author mrferrys
 *
 */
class plcHandler{
    
    public $netID; //"192.168.0.112.1.1"
    public $port;//801
    public $wsdl;//http://172.16.23.103/TcAdsWebservice/TcAdsWebService.WSDL
    public $webService;//http://172.16.23.103/TcAdsWebservice/TcAdsWebService.dll
    public $connectionTimeOut;//10 seconds
    public $indexGroups=array(
        "M"             =>  16416,
        "DB"            =>  16448,
        "Upload"        =>  61451,
        "UploadInfo"    =>  61452,
        "I"             =>  61472,
        "IX"            =>  61473,
        "Q"             =>  61488,
        "QX"            =>  61489,
        "SumRdWr"       =>  61568,
        "SumWr"         =>  61569
    );
    public $indexSizes=array(
        'X'=>   1,  //Single Bit    (ENCODED  BYTE-WISE)
        'B'=>   1,  //BYTE          (8        BITS)
        'W'=>   2,  //WORD          (16       BITS)
        'D'=>   4   //DWORD         (32       BITS)
        //'NONE'=>''//Single Bit    (ENCODED  BYTE-WISE)
    );
    public $symbolTable;
    private $soapClient=null;
    /**
     * 
     * @param string $webservice
     * @param string $wsdl
     * @param string $netid
     * @param string $port
     * @param number $timeout
     */
    function __construct($webservice="",$wsdl="",$netid="",$port="",$timeout=10)
    {
        $this->netID                =   $netid;
        $this->port                 =   $port;
        $this->wsdl                 =   $wsdl;
        $this->webService           =   $webservice;
        $this->connectionTimeOut    =   $timeout;
    }
    
    /**
     * 
     */
    public function getSymbolTable()
    {
        $uploadInfo     =   unpack('lSymbolCount/lUploadLen',$this->exec("Read",     "%UploadInfo0", "", "L",8,true));
        $strSymbol      =   $this->exec("Read",     "%Upload0", "", "C*",$uploadInfo['UploadLen'],true);
        $strAddr        =   0;

        for($i=0;$i<$uploadInfo['SymbolCount'];$i++)
        {
                $infoLen        =   unpack("LInfoLen/",substr($strSymbol, $strAddr,4))['InfoLen']."\n";
                $varName        =   substr($strSymbol, ($strAddr+VAR_NAME_OFFSET),($infoLen-VAR_NAME_OFFSET));
                $varOffset      =   unpack('lOffset',substr($strSymbol, ($strAddr+VAR_OFFSET_OFFSET),($infoLen-VAR_OFFSET_OFFSET)))['Offset'];
                $varIndexGroup  =   unpack('liGroup',substr($strSymbol, ($strAddr+VAR_INDEX_GROUP_OFFSET),($infoLen-VAR_INDEX_GROUP_OFFSET)))['iGroup'];
                $varSize        =   unpack('lvSize',substr($strSymbol, ($strAddr+VAR_SIZE_OFFSET),($infoLen-VAR_SIZE_OFFSET)))['vSize'];
                $varInfo        =   explode(chr(0), $varName);
                $this->symbolTable[$varInfo[0]] =   new plcVariable($varInfo[0],$varInfo[1],$varInfo,$varOffset,$varIndexGroup,$varSize);
                $strAddr        +=  $infoLen;
        }
    }
    /**
     * getOffset
     * @param unknown $address
     * @throws Exception
     * @return number[]
     */
    public function getOffset($address)
    {
        if(preg_match("/(\%)(?<indexGroup>(IX)|(QX)|(MX)|(DB)|(UploadInfo)|(Upload)|(SumRdWr)|(SumRd)|(SumWr)|[Q,I,M])(?<size>[A-Z,0-9,a-z])(?<offset>.*)/", $address,$matches)===0){
            throw new Exception("Error: Wrong Address");
        }
        $memoryPrefix   =   $this->indexGroups[$matches['indexGroup']];
        $indexSize      =   (is_numeric($matches['size'])    )?  1           :   ((isset($this->indexSizes[$matches['size']]))? $this->indexSizes[$matches['size']]:   1);
        $offsetString   =   ((is_numeric($matches['size'])   )? $matches['size'].$matches['offset'] :    $matches['offset']);
        $numbers        =   explode(".", $offsetString);
        $offset         =   0;
        foreach($numbers as $number)
        {
            $offset+=($number*$indexSize);
        }
        
        return array("offset"=>$offset,"indexGroup"=>$memoryPrefix);
    }
    
    /**
     * 
     * @param unknown $symbolName
     * @param unknown $value
     * @throws Exception
     * @return boolean
     */
    public function writeSymbolByName($symbolName,$value,$packType="")
    {
        $plcVar = $this->symbolTable[$symbolName];
        if($plcVar===null){throw new Exception("ERROR: VAR NAME NOT FOUND.");}
        $data   =   $value;
        $params =   array(
            "netId"         =>  $this->netID,
            "nPort"         =>  $this->port,
            "indexGroup"    =>  $this->symbolTable[$symbolName]->indexGroup,
            "indexOffset"   =>  $this->symbolTable[$symbolName]->offset,
            "pData"         =>  (($packType!="")?pack($packType,$data):$data)
        );
        
        //SOAP CLIENT PARAMS
        $options    =   array(
            "location"=>$this->webService
        );
        $configParams       =   array('connection_timeout'=>$this->connectionTimeOut,   'trace'=>true);
        
        //SOAP CLIENT
        if($this->soapClient === null){
            $this->soapClient         =  ($this->soapClient!=null)?$this->soapClient: new SoapClient($this->wsdl,$configParams);
        }
        $response           =   $this->soapClient->__soapCall("Write", $params,  $options);
        return true;
        
    }
    
    /**
     * 
     * @param unknown $symbolName
     * @throws Exception
     * @return mixed
     */
    public function readSymbolByName($symbolName,   $packType="")
    {
        $plcVar = $this->symbolTable[$symbolName];
        if($plcVar===null){throw new Exception("ERROR: VAR NAME NOT FOUND.");}
        
        $params = array(
            "netId"         =>  $this->netID,
            "nPort"         =>  $this->port,
            "indexGroup"    =>  $this->symbolTable[$symbolName]->indexGroup,
            "indexOffset"   =>  $this->symbolTable[$symbolName]->offset,
            "cbLen"         =>  $this->symbolTable[$symbolName]->size
        );
        
        //SOAP CLIENT PARAMS
        $options    =   array(
            "location"=>$this->webService
        );
        $configParams       =   array('connection_timeout'=>$this->connectionTimeOut,   'trace'=>true);
        
        //SOAP CLIENT
        if($this->soapClient === null){
            $this->soapClient         =  ($this->soapClient!=null)?$this->soapClient: new SoapClient($this->wsdl,$configParams);
        }
        $response           =   $this->soapClient->__soapCall("Read", $params,  $options);
        if(strpos($plcVar->varType,"ARRAY")!==false)
        {
            $openingKey =   strpos($plcVar->varType, "[");
            $closingKey =   strpos($plcVar->varType, "]");
            $arrayStart =   explode("..", (substr($plcVar->varType, $openingKey+1,($closingKey-$openingKey))) )[0];
            $arrayEnd   =   explode("..", (substr($plcVar->varType, $openingKey,($closingKey-$openingKey))) )[1];
    
            if(!is_numeric($arrayStart) || !is_numeric($arrayEnd)){return array();}
            $arraySize  =   (($arrayEnd-$arrayStart)+1);
           
            $format     =   $packType.$arraySize;
            return unpack($format, $response);
        }
       if($packType!=""){return unpack("$packType$symbolName/", $response)[$symbolName];}
        //echo "[DEBUG] $symbolName = ".$response." \n";
        return $response;
        
    }
    /**
     * exec
     * @param unknown $action
     * @param unknown $address
     * @param unknown $data
     */
    public function exec($action,$address="",$data="",$packType="c",$length=1,$rawdata=false)
    {
        //SOAP CLIENT PARAMS
        $options    =   array(
            "location"=>$this->webService
        );
        $configParams       =   array('connection_timeout'=>$this->connectionTimeOut,   'trace'=>true);
     
        //SOAP CALL PARAMS
        $params             =   array();
        //SOAP CLIENT
        if($this->soapClient === null){
            $this->soapClient         =  ($this->soapClient!=null)?$this->soapClient: new SoapClient($this->wsdl,$configParams);
        }
        $response   =   "";
        switch($action)
        {
            case "Write":
                $offset     =   $this->getOffset($address);
                $params     =   array(
                "netId"         =>  $this->netID,
                "nPort"         =>  $this->port,
                "indexGroup"    =>  $offset['indexGroup'],
                "indexOffset"   =>  $offset['offset'], //Bytes
                "pData"         =>  pack($packType,$data)
                );
                break;
            case "ReadWrite":
                break;
            case "Read":
                $offset             =   $this->getOffset($address);
                $params = array(
                "netId"         =>  $this->netID,
                "nPort"         =>  $this->port,
                "indexGroup"    =>  $offset['indexGroup'],
                "indexOffset"   =>  $offset['offset'],
                "cbLen"         =>  $length
                );
                $response       =   $this->soapClient->__soapCall($action, $params,  $options);
                $response       =   ($rawdata==true)?   $response   :   unpack($packType,$response);
               break;
            case "ReadState":
                $params     =   array(
                "netId"         =>  $this->netID,
                "nPort"         =>  $this->port
                );
                $response           =   $this->soapClient->__soapCall($action, $params,  $options);
                break;
            case "GetFunctions":
                $response   =  $this->soapClient->__getFunctions();
                break;
        }
        return $response;
    }
}

/**
 * plcVariable
 * @author mrferrys
 *
 */
class plcVariable{
    public $varName;
    public $varType;
    public $offset;
    public $indexGroup;
    public $size;
    public $rawInfo;
    
    /**
     * 
     * @param unknown $varName
     * @param unknown $varType
     * @param unknown $rawInfo
     * @param unknown $offset
     * @param unknown $indexGroup
     * @param unknown $size
     */
    function __construct($varName=null,$varType=null,$rawInfo=null,$offset=null,$indexGroup=null,$size=null)
    {
        $this->varName  =   $varName;
        $this->varType  =   $varType;
        $this->rawInfo  =   $rawInfo;
        $this->offset   =   $offset;
        $this->indexGroup   =   $indexGroup;
        $this->size         =   $size;
        //if(strpos($this->varName,"EPROD")!==false){echo "Name: ".$this->varName." Type: ".$this->varType." Size: ".$this->size." \n";}
        //if(strpos($this->varType,"ARRAY")!==false){echo "Name: ".$this->varName." Type: ".$this->varType." Size: ".$this->size." \n";}
        //echo "Name: ".$this->varName." Type: ".$this->varType." Size: ".$this->size." \n";
    }
}

?>