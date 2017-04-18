<?php

class LaraBlockTrailTest extends Orchestra\Testbench\TestCase
{
    private $client;
    private $passphrase;
    private $identifier;
    
    private $payIdentifier = 'AlexCarstensCattoriWallet1';
    private $payPassphrase = 'extreme-strong-password';
    
    public function setUp()
    {
        parent::setUp();
    }

    public function getPackageProviders($app)
    {
        return ['Blockavel\LaraBlocktrail\LaraBlocktrailServiceProvider'];
    }

    public function getPackageAliases($app)
    {
        return [
            'LaraBlocktrail' => 'Blockavel\LaraBlocktrail\LaraBlocktrailFacade',
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('larablocktrail.apiPrivateKey', getenv('BLOCKTRAIL_SECRET_API_KEY'));
        $app['config']->set('larablocktrail.apiPublicKey', getenv('BLOCKTRAIL_PUBLIC_API_KEY'));
        $app['config']->set('larablocktrail.network', getenv('BLOCKTRAIL_NETWORK'));
        $app['config']->set('larablocktrail.testnet', getenv('BLOCKTRAIL_TESTNET'));
        $app['config']->set('larablocktrail.version', getenv('BLOCKTRAIL_VERSION'));
    }
    
    private function randomString($length = 10)
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
    
    private function setPassphrase()
    {
        $this->passphrase = $this->randomString();
    }
    
    private function setIdentifier()
    {
        $this->identifier = $this->randomString();
    }
    
    public function testGetClient()
    {
        $res = LaraBlocktrail::getClient();
        $this->assertInstanceOf('Blocktrail\SDK\BlocktrailSDK', $res);
    }
    
    public function testWalletMethods()
    {
        $this->setIdentifier();
        $this->setPassphrase();
        
        $res = LaraBlocktrail::createWallet($this->identifier, $this->passphrase);
        $this->assertInstanceOf('Blocktrail\SDK\Wallet', $res[0]);
        
        $wallet = LaraBlockTrail::initWallet($this->payIdentifier, $this->payPassphrase);
        $this->assertInstanceOf('Blocktrail\SDK\Wallet', $wallet);
        
        $this->assertNull(LaraBlockTrail::lock($wallet));
        
        $address = LaraBlocktrail::getNewAddress($res[0]);
        
        $this->assertTrue(strlen($address) == 35);
        
        $this->assertNull(LaraBlocktrail::payAndLock($wallet, $this->payPassphrase, $address, 0.0001));
        
        $this->assertNull(LaraBlocktrail::unlock($wallet, $this->payPassphrase));
        
        $maxToSpend = LaraBlocktrail::getMaxSpendable($wallet);
        
        $this->assertArrayHasKey('max', (array) $maxToSpend);
        
        $isDeleted = LaraBlocktrail::easyDeleteWallet($this->identifier, $this->passphrase);
        
        $this->assertTrue($isDeleted);
        
        $identifier = LaraBlockTrail::getIdentifier($wallet);
        
        $this->assertNotNull($identifier);
        
        $this->assertArrayHasKey(
            'max', (array) LaraBlocktrail::walletMaxSpendable($identifier)
        );
        
        $this->assertTrue(count(LaraBlocktrail::getBalance($wallet)) == 2);
        
        $this->assertTrue(count(LaraBlocktrail::getNewAddressPair($wallet)) == 2);
    }
    
    public function testTransactionMethods()
    {
        $this->assertInstanceOf('Blocktrail\SDK\TransactionBuilder', LaraBlockTrail::txBuilder());
    }
}


