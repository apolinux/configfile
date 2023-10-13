<?php

use Apolinux\Config ;
use Apolinux\InvalidKeyException;
use Apolinux\FileNotFoundException;
use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase ;

/**
 * Description of ConfigTest
 *
 * @author drake
 */
class ConfigTest extends PHPUnit_Framework_TestCase{

    public function setUp() : void{
        parent::setUp();
    }

    private function createConfigFile($file){
        if(file_exists($file)){
            unlink($file);
        }
        file_put_contents($file, '<?php return ["test" => ["alfa" => 5 , "omega" => "009"] , "bla" => "fin"]; ');
    }

    protected function initConfig(){
        Config::init(__DIR__) ;
        $file = __DIR__ .'/config.php' ;

        // create config
        $this->createConfigFile($file);
    }

    public function testGetOk(){
        $this->initConfig();

        $this->assertEquals( 5 , Config::get('config.test.alfa') );
        $this->assertEquals( '009' , Config::get('config.test.omega') );

        $this->assertEquals('fin', Config::get('config.bla')) ;

        $this->assertEquals(["alfa" => 5 , "omega" => "009"] , Config::get('config.test')) ;
    }

    /**
     * @throws Config\InvalidKeyException
     */
    public function testGetNotExistItem(){
        $this->initConfig();
        $this->expectException(InvalidKeyException::class);

        Config::get('config.test.beta') ;
    }

    public function testGetNotExistFile(){
        $file = __DIR__ .'/config.php' ;
        Config::clearCacheFile();
        if(file_exists($file)) unlink($file);
        $this->expectException(FileNotFoundException::class);
        Config::get('config.test.beta') ;
    }

    public function testItemExistsOk(){
        $this->initConfig();
        $this->assertTrue(Config::itemExist('config.test.alfa'));
    }

    public function testItemExistsNotExists(){
        $this->initConfig();
        $this->assertFalse(Config::itemExist('config.test.beta'));
    }

    public function testSetOk(){
        $this->initConfig();
        Config::set('config.adam','word');
        $this->assertEquals('word', Config::get('config.adam'));
    }

    public function testSetNotExistFile(){
        $this->initConfig();
        $this->expectException(FileNotFoundException::class);
        Config::set('config2.adam','word');
    }

    public function testGetConfigToUserPasswd(){
        $this->initConfig();
        Config::set('config.userpwd',['user' => 'pwd']);
        $this->assertEquals("user:pwd",Config::getToUserPwd('config.userpwd'));
        Config::set('config.userpwd',['user' => 'pwd', 'user2' => 'pwd2']);
        $this->assertEquals("user:pwd",Config::getToUserPwd('config.userpwd',1));
    }

    public function testGetReplacedOk(){
        $this->initConfig();

        Config::set('config.base','blablabla') ;
        Config::set('config.testbase','%base%/algo');

        $this->assertEquals( 'blablabla/algo' , Config::getReplaced('config.testbase') );
    }

    public function tearDown() : void {
        if(file_exists(__DIR__ .'/config.php')) unlink(__DIR__ .'/config.php') ;
    }

    public function testSweep(){
        $this->initConfig();
        Config::set('config.info.password','non');
        $result = Config::sweep('config') ;

        $this->assertEquals(5, $result['test.alfa']) ;
        $this->assertEquals('009', $result['test.omega']) ;
        $this->assertEquals('non', $result['info.password']) ;
    }

    public function testGetAll(){
        $this->initConfig();
        Config::set('config.bla','fin');
        Config::set('config.name','Yulk');
        $result = Config::getAll('config') ;
        $this->assertTrue(is_array($result));
        $this->assertEquals('fin', $result['bla']) ;
        $this->assertEquals('Yulk', $result['name']) ;
    }
 }
