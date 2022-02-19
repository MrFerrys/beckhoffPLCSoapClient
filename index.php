<?php
/**
 * LIB REST Countries 
 */
//EXECUTION TIME
ini_set('max_execution_time', '0');

//ERROR REPORTING
//error_reporting(0);
error_reporting(E_ALL);

//LIBS
use mrferrys\beckhoffplcsoapclient\plcHandler as plcHandler;

/**                                                                        
 *  MAIN
 */
try{
	$pHandler= new plcHandler(
        "http://172.16.23.102/TcAdsWebService/TcAdsWebService.dll",
        "http://172.16.23.102/TcAdsWebService/TcAdsWebService.WSDL",
        "5.21.99.2.1.1",
        "801");
    
    print_r($pHandler->exec("GetFunctions"));
    
    $pHandler->getSymbolTable();
    foreach($pHandler->symbolTable as $k=>$v){
        echo "$k \n";
    }
    echo "STARTING\n";
    $pHandler->writeSymbolByName(".CLAS2_PARO_EMERGENCIA",true,"c");
	
    sleep(5);
    
	$pHandler->writeSymbolByName(".CLAS2_PARO_EMERGENCIA",false,"c");
    echo "DONE\n";
  
   $pHandler= new plcHandler(  
        "http://172.16.22.140/TcAdsWebService/TcAdsWebService.dll",
        "http://172.16.22.140/TcAdsWebService/TcAdsWebService.WSDL",
        "172.16.22.140.1.1",
        "801");
  
    $pHandler->getSymbolTable();
    foreach($pHandler->symbolTable as $k=>$v){
        echo "$k \n";
    }
	
}catch(\Exception $e){
    echo $e->getMessage();
}
?>

