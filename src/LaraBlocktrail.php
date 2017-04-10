<?php

namespace Blockavel\LaraBlocktrail;

use Blocktrail\SDK\BlocktrailSDK;
use Blocktrail\SDK\BackupGenerator;
use Blocktrail\SDK\Wallet;
use Blocktrail\SDK\TransactionBuilder;
use Blocktrail\SDK\UTXO;
use Blocktrail\SDK\Services\BlocktrailBitcoinService;
use Blocktrail\SDK\WalletSweeper;

class LaraBlocktrail
{
    protected $client;

    public function __construct()
    {
        $this->client = new BlocktrailSDK(
            config('larablocktrail.apiPublicKey'),
            config('larablocktrail.apiPrivateKey'),
            config('larablocktrail.network'),
            config('larablocktrail.testnet'),
            config('larablocktrail.version')
        );
    }
    
    public function getClient()
    {
        return $this->client;
    }
    
    /**
     * Data Methods
     */
    
    public function transaction($txhash) 
    {
        return $this->client->transaction($txhash);
    }
    
    public function blockTransactions($block, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->blockTransactions($block, $page, $limit, $sortDir);
    }
    
    public function block($block)
    {
        return $this->client->block($block);
    }
    
    public function blockLatest()
    {
        return $this->client->blockLatest();
    }
    
    public function allBlocks($page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->allBlocks($page. $limit. $sortDir);
    }
    
    public function addressUnspentOutputs($address, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->addressUnspentOutputs($address, $page, $limit, $sortDir);
    }
    
    public function addressUnconfirmedTransactions($address, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->addressUnconfirmedTransactions($address, $page, $limit, $sortDir);
    }
    
    public function addressTransactions($address, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->addressTransactions($address, $page, $limit, $sortDir);
    }
    
    public function address($address)
    {
        return $this->client->address($address);
    }
    
    /* Wallet Methods */
    
    /**
     * initialize a previously created wallet
     *
     * Either takes one argument:
     * @param array $options
     *
     * Or takes two arguments (old, deprecated syntax):
     * (@nonPHP-doc) @param string    $identifier             the wallet identifier to be initialized
     * (@nonPHP-doc) @param string    $password               the password to decrypt the mnemonic with
     *
     * @return WalletInterface
     * @throws \Exception
     */
    public function initWallet($indentifier, $passphrase)
    {
        return $this->client->initWallet($indentifier, $passphrase);
    }
    
    /**
     * initialize a previously created wallet
     *
     * Either takes one argument:
     * @param array $options
     *
     * Or takes two arguments (old, deprecated syntax):
     * (@nonPHP-doc) @param string    $identifier             the wallet identifier to be initialized
     * (@nonPHP-doc) @param string    $privateKey             the privateKey to access wallet
     *
     * @return WalletInterface
     * @throws \Exception
     */
    public function initWalletWithPK($identifier, $privateKey)
    {
        return $this->client->initWallet([
            "identifier" => $identifier,
            "primary_private_key" => $primaryPrivateKey,
            "primary_mnemonic" => false
        ]);
    }
    
    /**
     * create a new wallet
     *   - will generate a new primary seed (with password) and backup seed (without password)
     *   - send the primary seed (BIP39 'encrypted') and backup public key to the server
     *   - receive the blocktrail co-signing public key from the server
     *
     * Takes three arguments (old, deprecated syntax):
     * (@nonPHP-doc) @param      $identifier
     * (@nonPHP-doc) @param      $password
     * (@nonPHP-doc) @param int  $keyIndex         override for the blocktrail cosigning key to use
     *
     * @return array[WalletInterface, (string)primaryMnemonic, (string)backupMnemonic]
     * @throws \Exception
     */
    public function createWallet($passphrase, $identifier = null)
    {
        if(is_null($identifier))
        {
            $bytes = openssl_random_pseudo_bytes(10);
            $identifier = bin2hex($bytes);
        }
        
        return list(
            $wallet, 
            $primaryMnemonic,
            $backupMnemonic, 
            $blocktrailPublicKeys
        )  = $this->client->createNewWallet($identifier, $passphrase);
    }
    
    /**
     * upgrade wallet to use a new account number
     * the account number specifies which blocktrail cosigning key is used
     *
     * @param string    $identifier             the wallet identifier to be upgraded
     * @param int       $keyIndex               the new account to use
     * @param array     $primaryPublicKey       BIP32 extended public key - [key, path]
     * @return mixed
     */
    public function upgradeKeyIndex($identifier, $keyIndex, $primaryPublicKey)
    {
        return $this->client->upgradeKeyIndex($identifier, $keyIndex, $primaryPublicKey);
    }
    
    public function backupInfo($primaryMnemonic, $backupMnemonic, $blocktrailPublicKeys)
    {
        return new BackupGenerator($primaryMnemonic, $backupMnemonic, $blocktrailPublicKeys);
    }
    
    public function generateBackupPDF(BackupGenerator $backupGenerator)
    {
        $backupGenerator->generatePDF();
    }
    
    public function generateBackupHTML(BackupGenerator $backupGenerator)
    {
        $backupGenerator->generateHTML();
    }
    
    public function generateBackupImg(BackupGenerator $backupGenerator, $fileName)
    {
        $backupGenerator->generateImg($fileName);
    }
    
    public function getSatoshiAmount($btcAmount)
    {
        return BlocktrailSDK::toSatoshi($btcAmount);
    }
    
    public function getBTCAmount($satoshiAmount)
    {
        return BlocktrailSDK::toBTC($satoshiAmount);
    }
    
    /**
     * return list of Blocktrail co-sign extended public keys
     *
     * @param Wallet $wallet
     * @return array[]      [ [xpub, path] ]
     */
    public function getBlocktrailPublicKeys(Wallet $wallet)
    {
        return $wallet->getBlocktrailPublicKeys();
    }
    
    /**
     * return the wallet primary mnemonic (for backup purposes)
     * 
     * @param  Wallet   $wallet
     * @return string
     */
    public function getPrimaryMnemonic(Wallet $wallet) {
        return $wallet->getPrimaryMnemonic();
    }
    
    /**
     * get the path (and redeemScript) to specified address
     *
     * @param Wallet $wallet
     * @param string $address
     * @return array
     */
    public function getPathForAddress(Wallet $wallet, $address)
    {
        return $wallet->getPathForAddress($address);
    }
    
    /**
     * @param Wallet    $wallet
     * @return int
     */
    public function getOptimalFeePerKB(Wallet $wallet)
    {
        return $wallet->getOptimalFeePerKB();
    }
    
    /**
     * @param Wallet    $wallet
     * @return int
     */
    public function getLowPriorityFeePerKB(Wallet $wallet)
    {
        return $wallet->getLowPriorityFeePerKB();
    }
    
    /**
     * get the balance for the wallet
     * 
     * @param  Wallet       $wallet
     * @return int[]        [confirmed, unconfirmed]
     */
    public function getBalance(Wallet $wallet)
    {
        return $wallet->getBalance();
    }
    
    /**
     * generate a new derived private key and return the new address for it
     * 
     * @param  Wallet       $wallet
     * @return string
     */
    public function getNewAddress(Wallet $wallet)
    {
        return $wallet->getNewAddress();
    }
    
    /**
     * generate a new derived key and return the new path and address for it
     * 
     * @param  Wallet       $wallet
     * @return string[]     [path, address]
     */
    public function getNewAddressPair(Wallet $wallet)
    {
        return $wallet->getNewAddressPair();
    }
    
    public function txBuilder()
    {
        return new TransactionBuilder();
    }
    
    /**
     * build inputs and outputs lists for TransactionBuilder
     *
     * @param Wallet             $wallet
     * @param TransactionBuilder $txBuilder
     * @return array
     * @throws \Exception
     */
    public function buildTx(Wallet $wallet, TransactionBuilder $txBuilder)
    {
        return $wallet->buildTx($txBuilder);
    }
    
    /**
     * 'fund' the txBuilder with UTXOs (modified in place)
     *
     * @param TransactionBuilder    $txBuilder
     * @param Wallet                $wallet
     * @return TransactionBuilder
     */
    public function getCoinSelection(Wallet $wallet, TransactionBuilder $txBuilder)
    {
        return $wallet->coinSelectionForTxBuilder($txBuilder);
    }
    
    /**
     * determine the fee to be used in transaction as well as the possible change
     * 
     * @param Wallet             $wallet
     * @param TransactionBuilder $txBuilder
     * @param integer            $lowPriorityFeePerKB
     * @param integer            $optimalFeePerKB
     */
    public function determineFeeAndChange(Wallet $wallet, TransactionBuilder $txBuilder, $lowPriorityFeePerKB, $optimalFeePerKB)
    {
        return list($fee, $change) = $wallet->determineFeeAndChange($txBuilder, $optimalFeePerKB, $lowPriorityFeePerKB);
    }
    
    /**
     * create, sign and send transction based on TransactionBuilder
     *
     * @param Wallet             $wallet
     * @param TransactionBuilder $txBuilder
     * @param bool $apiCheckFee     let the API check if the fee is correct
     * @return string
     * @throws \Exception
     */
    public function sendTx(Wallet $wallet, TransactionBuilder $txBuilder, $apiCheckFee = false)
    {
        return $wallet->sendTx($txBuilder, $apiCheckFee);
    }
    
    /**
     * verify ownership of an address
     * @param  string  $address     address hash
     * @param  string  $signature   a signed message (the address hash) using the private key of the address
     * @return array                associative array containing the response
     */
    public function verifyAddress($address, $signature)
    {
        return $this->client->verifyAddress($address, $signature);
    }
    
    /**
     * return the wallet identifier
     *
     * @return string
     */
    public function getIdentifier(Wallet $wallet)
    {
        return $wallet->getIdentifier();
    }
    
    /**
     * do wallet discovery (slow)
     *
     * @param Wallet  $wallet
     * @param int     $gap        the gap setting to use for discovery
     * @return int[]              [confirmed, unconfirmed]
     */
    public function doDiscovery(Wallet $wallet)
    {
        return $wallet->doDiscovery();
    }
    
    /**
     * determine max spendable from wallet after fees
     * @param  Wallet object  $wallet
     * @return string
     * @throws BlocktrailSDKException
     */
    public function getMaxSpendable(Wallet $wallet)
    {
        return $wallet->getMaxSpendable();
    }
    
    /**
     * get all UTXOs for the wallet (paginated)
     *
     * @param  Wallet  $wallet
     * @param  integer $page    pagination: page number
     * @param  integer $limit   pagination: records per page (max 500)
     * @param  string  $sortDir pagination: sort direction (asc|desc)
     * @return array            associative array containing the response
     */
    public function utxos(Wallet $wallet)
    {
        return $wallet->utxos();
    }
    
    /**
     * get all transactions for the wallet (paginated)
     *
     * @param  Wallet  $wallet 
     * @param  integer $page    pagination: page number
     * @param  integer $limit   pagination: records per page (max 500)
     * @param  string  $sortDir pagination: sort direction (asc|desc)
     * @return array            associative array containing the response
     */
    public function transactions(Wallet $wallet)
    {
        return $wallet->transactions();
    }
    
    /**
     * get all addresses for the wallet (paginated)
     * 
     * @param  Wallet  $wallet
     * @param  integer $page    pagination: page number
     * @param  integer $limit   pagination: records per page (max 500)
     * @param  string  $sortDir pagination: sort direction (asc|desc)
     * @return array            associative array containing the response
     */
    public function addresses(Wallet $wallet)
    {
        return $wallet->addresses();
    }
    
    /**
     * create, sign and send a transaction
     * 
     * @param Wallet   $wallet    
     * @param array    $outputs             [address => value, ] or [[address, value], ] or [['address' => address, 'value' => value], ] coins to send
     *                                      value should be INT
     * @param string   $changeAddress       change address to use (autogenerated if NULL)
     * @param bool     $allowZeroConf
     * @param bool     $randomizeChangeIdx  randomize the location of the change (for increased privacy / anonimity)
     * @param string   $feeStrategy
     * @param null|int $forceFee            set a fixed fee instead of automatically calculating the correct fee, not recommended!
     * @return string the txid / transaction hash
     * @throws \Exception
     */
    public function pay(Wallet $wallet, array $outputs, $allowZeroConf = false, $randomizeChangeIdx = true, $feeStrategy = self::FEE_STRATEGY_OPTIMAL, $forceFee = null)
    {
        return $wallet->pay($outputs, $allowZeroConf, $randomizeChangeIdx, $feeStrategy, $forceFee);
    }
    
    /**
     * get address for the specified path
     *
     * @param Wallet            $wallet
     * @param string|BIP32Path  $path
     * @return string
     */
    public function getAddressByPath(Wallet $wallet, $path)
    {
        return $wallet->getAddressByPath($path);
    }
    
    /**
     * unlock wallet so it can be used for payments
     *
     * @param Wallet   $wallet
     * @param          $options ['primary_private_key' => key] OR ['passphrase' => pass]
     * @param callable $fn
     * @return bool
     */
    public function unlock(Wallet $wallet)
    {
        return $wallet->unlock();
    }
    
    /**
     * lock the wallet (unsets primary private key)
     * 
     * @param Wallet    $wallet
     * @return void
     */ 
    public function lock(Wallet $wallet)
    {
        return $wallet->lock();
    }
    
    /**
     * unlock wallet to pay and lock the wallet (unsets primary private key)
     * 
     * @param Wallet    $wallet
     * @param string    $passphrase
     * @param string    $address
     * @param float     $btcAmount
     * @return void
     */
    public function payAndLock(Wallet $wallet, $passphrase, $address, $btcAmount)
    {
        return $wallet->unlock(["passphrase" => $passphrase], 
            function(Wallet $wallet) use($address, $btcAmount) {
                $wallet->pay([
                    $address => $this->getSatoshiAmount($btcAmount)
                ]);
            }
        );
    }
    
    /**
     * get the wallet data from the server
     *
     * @param string    $identifier             the identifier of the wallet
     * @return mixed
     */
    public function getWallet($identifier)
    {
        return $this->client->getWallet($identifier);
    }
    
    /**
     * delete a wallet from the server
     *  the checksum address and a signature to verify you ownership of the key of that checksum address
     *  is required to be able to delete a wallet
     *
     * @param string    $identifier             the identifier of the wallet
     * @param string    $checksumAddress        the address for your master private key (and the checksum used when creating the wallet)
     * @param string    $signature              a signature of the checksum address as message signed by the private key matching that address
     * @param bool      $force                  ignore warnings (such as a non-zero balance)
     * @return mixed
     */
    public function deleteWallet($identifier, $checksumAddress, $signature, $force = false)
    {
        return $this->client->deleteWallet($identifier, $checksumAddress, $signature, $force);
    }
    
    /**
     * get the balance for the wallet
     *
     * @param string    $identifier             the identifier of the wallet
     * @return array
     */
    public function getWalletBalance($identifier)
    {
        return $this->client->getWalletBalance($identifier);
    }
    
    /**
     *
     * @param string   $identifier the identifier of the wallet
     * @param bool     $allowZeroConf
     * @param string   $feeStrategy
     * @param null|int $forceFee
     * @param int      $outputCnt
     * @return array
     * @throws \Exception
     */
    public function walletMaxSpendable($identifier, $allowZeroConf = false, $feeStrategy = Wallet::FEE_STRATEGY_OPTIMAL, $forceFee = null, $outputCnt = 1)
    {
        return $this->client->walletMaxSpendable($identifier, $allowZeroConf, $feeStrategy, $forceFee, $outputCnt);
    }
    
    /**
     * @return array        ['optimal_fee' => 10000, 'low_priority_fee' => 5000]
     */
    public function feePerKB()
    {
        return $this->client->feePerKB();
    }
    
    /**
     * get the current price index
     *
     * @return array        eg; ['USD' => 287.30]
     */
    public function price()
    {
        return $this->client->price();
    }
    
    /**
     * setup webhook for wallet
     *
     * @param string    $identifier         the wallet identifier for which to create the webhook
     * @param string    $webhookIdentifier  the webhook identifier to use
     * @param string    $url                the url to receive the webhook events
     * @return array
     */
    public function setupWalletWebhook($identifier, $webhookIdentifier, $url)
    {
        return $this->client->setupWalletWebhook($identifier, $webhookIdentifier, $url);
    }
    
    /**
     * delete webhook for wallet
     *
     * @param string    $identifier         the wallet identifier for which to delete the webhook
     * @param string    $webhookIdentifier  the webhook identifier to delete
     * @return array
     */
    public function deleteWalletWebhook($identifier, $webhookIdentifier)
    {
        return $this->client->deleteWalletWebhook($identifier, $webhookIdentifier);
    }
    
    /**
     * lock a specific unspent output
     *
     * @param     $identifier
     * @param     $txHash
     * @param     $txIdx
     * @param int $ttl
     * @return bool
     */
    public function lockWalletUTXO($identifier, $txHash, $txIdx, $ttl = 3)
    {
        return $this->client->lockWalletUTXO($identifier, $txHash, $txIdx, $ttl);
    }
    
    /**
     * unlock a specific unspent output
     *
     * @param     $identifier
     * @param     $txHash
     * @param     $txIdx
     * @return bool
     */
    public function unlockWalletUTXO($identifier, $txHash, $txIdx)
    {
        return $this->client->unlockWalletUTXO($identifier, $txHash, $txIdx);
    }
    
    /**
     * get all transactions for wallet (paginated)
     *
     * @param  string  $identifier  the wallet identifier for which to get transactions
     * @param  integer $page        pagination: page number
     * @param  integer $limit       pagination: records per page (max 500)
     * @param  string  $sortDir     pagination: sort direction (asc|desc)
     * @return array                associative array containing the response
     */
    public function walletTransactions($identifier, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->walletTransactions($identifier, $page, $limit, $sortDir);
    }
    
    /**
     * get all addresses for wallet (paginated)
     *
     * @param  string  $identifier  the wallet identifier for which to get addresses
     * @param  integer $page        pagination: page number
     * @param  integer $limit       pagination: records per page (max 500)
     * @param  string  $sortDir     pagination: sort direction (asc|desc)
     * @return array                associative array containing the response
     */
    public function walletAddresses($identifier, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->walletAddresses($identifier, $page, $limit, $sortDir);
    }
    
    /**
     * get all UTXOs for wallet (paginated)
     *
     * @param  string  $identifier  the wallet identifier for which to get addresses
     * @param  integer $page        pagination: page number
     * @param  integer $limit       pagination: records per page (max 500)
     * @param  string  $sortDir     pagination: sort direction (asc|desc)
     * @return array                associative array containing the response
     */
    public function walletUTXOs($identifier, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        return $this->client->walletUTXOs($identifier, $page, $limit, $sortDir);
    }
    
    /**
     * get a paginated list of all wallets associated with the api user
     *
     * @param  integer          $page    pagination: page number
     * @param  integer          $limit   pagination: records per page
     * @return array                     associative array containing the response
     */
    public function allWallets($page = 1, $limit = 20)
    {
        return $this->client->allWallets($page, $limit);
    }
    
    /**
     * check if wallet is locked
     *
     * @return bool
     */
    public function isLocked(Wallet $wallet)
    {
        return $wallet->isLocked();
    }
    
    /* TransactionBuilder Methods */
    
    /**
     * @param TransactionBuilder $txBuilder
     * @return UTXO[]
     */
    public function getUtxos(TransactionBuilder $txBuilder)
    {
        return $txBuilder->getUtxos();
    }
    
    /**
     * @param TransactionBuilder $txBuilder
     * @return array
     */
    public function getOutputs(TransactionBuilder $txBuilder)
    {
        return $txBuilder->getOutputs();
    }
    
    /**
     * add OP_RETURN output
     *
     * $data will be bin2hex and will be prefixed with a proper OP_PUSHDATA
     * 
     * @param TransactionBuilder $txBuilder
     * @param string $data
     * @param bool   $allowNonStandard when TRUE will allow scriptPubKey > 40 bytes (so $data > 39 bytes)
     * @return TransactionBuilder
     * @throws BlocktrailSDKException
     */
    public function addOpReturn(TransactionBuilder $txBuilder, $data, $allowNonStandard = false)
    {
        return $txBuilder->addOpReturn($string, $allowNonStandard);
    }
    
    /**
     * @param TransactionBuilder $txBuilder
     * @param string $paymentAddress
     * @param int    $satoshiAmount
     * @return TransactionBuilder
     * @throws \Exception
     */
    public function addRecipients(TransactionBuilder $txBuilder, $paymentAddress, $satoshiAmount)
    {
        return $txBuilder->addRecipient($paymentAddress, $satoshiAmount);
    }
    
    /**
     * set desired fee (normally automatically calculated)
     *
     * @param TransactionBuilder    $txBuilder
     * @param int                   $value
     * @return $this
     */
    public function setFee(TransactionBuilder $txBuilder, $value)
    {
        return $txBuilder->setFee($value);
    }
    
    /**
     * @param TransactionBuilder $txBuilder
     * @return int|null
     */
    public function getFee(TransactionBuilder $txBuilder)
    {
        return $txBuilder->getFee();
    }
    
    /**
     * 
     * @param string $feeStrategy
     * @return $this
     * @throws BlocktrailSDKException
     */
    public function setFeeStrategy(TransactionBuilder $txBuilder, $feeStrategy)
    {
        return $txBuilder->setFeeStrategy($feeStrategy);
    }
    
    /**
     * @param TransactionBuilder $txBuilder
     * @return string
     */
    public function getFeeStrategy(TransactionBuilder $txBuilder) 
    {
        return $txBuilder->getFeeStrategy();
    }
    
    public function updateOutputValue(TransactionBuilder $txBuilder, $satoshiAmount, $fee, $val = 0)
    {
        return $txBuilder->updateOutputValue($val, $satoshiAmount - $fee);
    }
    
    /**
     * replace the currently set UTXOs with a new set
     *
     * @param TransactionBuilder $txBuilder
     * @param UTXO[] $utxos
     * @return $this
     */
    public function setUtxos(TransactionBuilder $txBuilder, $utxos)
    {
        return $txBuilder->setUtxos($utxos);
    }
    
    public function minimizeTxFee(TransactionBuilder $txBuilder, $fee)
    {
        $outsum = array_sum(array_column($txBuilder->getOutputs(), 'value')) + $fee;
        $utxosum = array_sum(array_map(function(UTXO $utxo) { return $utxo->value; }, $txBuilder->getUtxos()));
        $utxos = $txBuilder->getUtxos();
        
        foreach ($utxos as $idx => $utxo) 
        {
            if ($utxosum - $utxo->value >= $outsum) 
            {
                unset($utxos[$idx]);
                $utxosum -= $utxo->value;
            }
        }
        
        $txBuilder->setUtxos(array_values($utxos));
        
        return $txBuilder;
    }
    
    public function bitcoinClient()
    {
        return new BlocktrailBitcoinService(
            config('larablocktrail.apiPublicKey'),
            config('larablocktrail.apiPrivateKey'),
            config('larablocktrail.network'),
            config('larablocktrail.testnet'),
            config('larablocktrail.version')
        );
    }
    
    public function walletSweeper(
        $primaryMnemonic, $primaryPassphrase, $backupMnemonic, 
        $blocktrailKeys, BlocktrailBitcoinService $bitcoinClient, 
        $network = 'btc',  $_testnet = true)
    {
        return new WalletSweeper(
            $primaryMnemonic, $primaryPassphrase, $backupMnemonic, 
            $blocktrailKeys, $bitcoinClient, $network, $_testnet
        );
    }
    
    public function sweepWallet(WalletSweeper $walletSweeper, $toAddress)
    {
        return $walletSweeper->sweepWallet($toAddress);
    }
    
    /**
     * Webhook Methods
     */
    
    public function setupWebhook($url, $identifier)
    {
        return $this->client->setupWebhook($url, $identifier);
    }
    
    public function allWebhooks($page = 1, $limit = 20)
    {
        return $this->client->allWebhooks($page, $limit);
    }
    
    public function getWebhook($identifier)
    {
        return $this->client->getWebhook($identifier);
    }
    
    public function updateWebhook($identifier, $newUrl = null, $newIdentifier = null)
    {
        return $this->client->updateWebhook($identifier, $newUrl, $newIdentifier);
    }
    
    public function deleteWebhook($identifier)
    {
        return $this->client->deleteWebhook($identifier);
    }
    
    public function getWebhookEvents($identifier, $page = 1, $limit = 20)
    {
        return $this->getWebhookEvents($identifier, $page, $limit);
    }
    
    public function subscribeTransaction($identifier, $transaction, $confirmations = 6) 
    {
        return $this->client->subscribeTransaction($identifier, $transaction, $confirmations); 
    }
    
    public function subscribeAddressTransactions($identifier, $address)
    {
        return $this->client->subscribeAddressTransactions($identifier, $address);   
    }
    
    public function batchSubscribeAddressTransactions($identifier, $batchData)
    {
        return $this->client->subscribeAddressTransactions($identifier, $batchData);
    }
    
    public function subscribeNewBlocks($identifier)
    {
        return $this->client->subscribeNewBlocks($identifier);   
    }
    
    public function unsubscribeTransaction($identifier, $transaction)
    {
        return $this->client->unsubscribeTransaction($identifier, $transaction);
    }
    
    public function unsubscribeAddressTransactions($identifier, $address)
    {
        return $this->client->unsubscribeAddressTransactions($identifier, $address);
    }
    
    public function unsubscribeNewBlocks($identifier) 
    {
        return $this->client->unsubscribeNewBlocks($identifier);
    }
    
    public function getWebhookPayload()
    {
        return BlocktrailSDK::getWebhookPayload();
    }
}

