<?php
// includes/zatca/CryptoService.php

class ZATCA_CryptoService {
    
    private $opensslPath = 'c:\\xampp\\apache\\bin\\openssl.exe';

    /**
     * Generate ECDSA secp256k1 Private Key
     */
    public function generatePrivateKey() {
        $keyPath = tempnam(sys_get_temp_dir(), 'zatca_key');
        
        // Generate EC parameters and private key
        $cmd = "\"{$this->opensslPath}\" ecparam -name secp256k1 -genkey -out \"{$keyPath}\" 2>&1";
        shell_exec($cmd);
        
        if (file_exists($keyPath)) {
            $key = file_get_contents($keyPath);
            unlink($keyPath);
            return $key;
        }
        return false;
    }

    /**
     * Generate CSR using Private Key and Company info
     */
    public function generateCsr($privateKey, $companyData) {
        $keyPath = tempnam(sys_get_temp_dir(), 'zatca_key');
        file_put_contents($keyPath, $privateKey);
        
        $confPath = tempnam(sys_get_temp_dir(), 'zatca_conf');
        $csrPath = tempnam(sys_get_temp_dir(), 'zatca_csr');

        // ZATCA specific CSR config structure
        $config = "
[ req ]
prompt = no
default_bits = 2048
default_md = sha256
distinguished_name = req_distinguished_name
req_extensions = v3_req

[ req_distinguished_name ]
C = SA
OU = {$companyData['branch']}
O = {$companyData['name']}
CN = {$companyData['common_name']}

[ v3_req ]
1.3.6.1.4.1.311.20.2 = ASN1:UTF8String:{$companyData['env']}
2.5.4.17 = ASN1:UTF8String:{$companyData['postal_code']}
2.5.4.11 = ASN1:UTF8String:{$companyData['branch']}
2.5.4.10 = ASN1:UTF8String:{$companyData['name']}
1.2.903.111.2.1.2 = ASN1:UTF8String:1-{$companyData['cr']}|2-{$companyData['branch']}|3-{$companyData['uuid']}
2.5.4.5 = ASN1:UTF8String:{$companyData['vat']}
2.5.4.26 = ASN1:UTF8String:{$companyData['city']}
2.5.4.9 = ASN1:UTF8String:{$companyData['street']}
";

        file_put_contents($confPath, $config);

        $cmd = "\"{$this->opensslPath}\" req -new -key \"{$keyPath}\" -config \"{$confPath}\" -out \"{$csrPath}\" -reqexts v3_req 2>&1";
        shell_exec($cmd);

        $csr = false;
        if (file_exists($csrPath) && filesize($csrPath) > 0) {
            $csr = file_get_contents($csrPath);
        }

        unlink($keyPath);
        unlink($confPath);
        unlink($csrPath);

        return $csr;
    }

    /**
     * Compute SHA-256 hash of a string (XML) and return Base64
     */
    public function computeHashBase64($content) {
        return base64_encode(hash('sha256', $content, true));
    }

    /**
     * Sign the Hash using the Private Key
     */
    public function signHash($hashBase64, $privateKey) {
        // Decode the hash back to binary
        $hashBinary = base64_decode($hashBase64);
        
        $keyPath = tempnam(sys_get_temp_dir(), 'zatca_key');
        file_put_contents($keyPath, $privateKey);
        
        $hashPath = tempnam(sys_get_temp_dir(), 'zatca_hash');
        file_put_contents($hashPath, $hashBinary);
        
        $sigPath = tempnam(sys_get_temp_dir(), 'zatca_sig');
        
        // Sign the raw hash
        $cmd = "\"{$this->opensslPath}\" pkeyutl -sign -inkey \"{$keyPath}\" -in \"{$hashPath}\" -out \"{$sigPath}\" 2>&1";
        shell_exec($cmd);
        
        $signature = false;
        if (file_exists($sigPath) && filesize($sigPath) > 0) {
            $signature = base64_encode(file_get_contents($sigPath));
        }
        
        unlink($keyPath);
        unlink($hashPath);
        unlink($sigPath);
        
        return $signature;
    }
}
?>
