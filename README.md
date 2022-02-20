# beckhoffPLCSoapClient
SoapClient to communicate with BeckHoff PLC. Library made in PHP based on TcAdsWebService JavaScript Library. 

## Description

SoapClient to communicate with BeckHoff PLC. Library made in PHP based on TcAdsWebService JavaScript Library. 

## Getting Started

### Dependencies

* PHP >= 5

### Installation
composer require mrferrys/beckhoffplcsoapclient

### Usage

* How to use it.
```
	use mrferrys\beckhoffplcsoapclient\plcHandler as plcHandler;

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
```
## Authors

MrFerrys  

## Version History

* 1.0.0
    * Initial Release (X.Y.Z MAJOR.MINOR.PATCH)

## License

This project is licensed under the MiT License - see the LICENSE file for details

## Acknowledgments

TcAdsWebService
* [TcAdsWebService.js](https://infosys.beckhoff.com/english.php?content=../content/1033/tcadswebservice.js/html/intro.html&id=)
