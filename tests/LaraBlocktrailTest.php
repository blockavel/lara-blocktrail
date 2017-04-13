<?php

class LaraBlockTrailTest extends Orchestra\Testbench\TestCase
{
    protected $client;
    protected $passphrase;
    protected $identifier;
    protected $checksumAddress;
    protected $signature;
    
    public function setUp()
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return ['Blockavel\LaraBlocktrail\LaraBlocktrailServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaraBlocktrail' => 'Blockavel\LaraBlocktrail\LaraBlocktrailFacade',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('larablocktrail.apiPrivateKey', getenv('BLOCKTRAIL_SECRET_API_KEY'));
        $app['config']->set('larablocktrail.apiPublicKey', getenv('BLOCKTRAIL_PUBLIC_API_KEY'));
        $app['config']->set('larablocktrail.network', getenv('BLOCKTRAIL_NETWORK'));
        $app['config']->set('larablocktrail.testnet', getenv('BLOCKTRAIL_TESTNET'));
        $app['config']->set('larablocktrail.version', getenv('BLOCKTRAIL_VERSION'));
    }
    
    protected function randomString($length = 10)
    {
        $str = '';
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        return $str;
    }
    
    protected function setPassphrase()
    {
        $this->passphrase = $this->randomString();
    }
    
    protected function setIdentifier()
    {
        $this->identifier = $this->randomString();
    }
    
    public function testGetClient()
    {
        
        $res = LaraBlocktrail::getClient();
        $this->assertInstanceOf('Blocktrail\SDK\BlocktrailSDK', $res);
    }
    
    public function testCreateWallet()
    {
        $this->setIdentifier();
        $this->setPassphrase();
        $res = LaraBlocktrail::createWallet($this->identifier, $this->passphrase);
        $this->assertInstanceOf('Blocktrail\SDK\Wallet', $res[0]);
    }
}


