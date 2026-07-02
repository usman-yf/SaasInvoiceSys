<?php
// includes/zatca/ApiClient.php

class ZATCA_ApiClient {
    private $env;
    private $cert;
    private $secret;

    public function __construct($env, $cert, $secret) {
        $this->env = $env;
        $this->cert = $cert;
        $this->secret = $secret;
    }

    /**
     * MOCK: Report Simplified Tax Invoice (B2C)
     */
    public function reportInvoice($invoiceHash, $xmlContent, $uuid) {
        // In a real environment, this would do a cURL POST to ZATCA Core API
        // For now, we mock the ZATCA response as per user request.
        
        sleep(1); // Simulate network latency
        
        $success = true;
        
        if ($success) {
            return [
                'status' => 'REPORTED',
                'validationResults' => [
                    'infoMessages' => [
                        ['type' => 'INFO', 'code' => 'XSD_ZATCA_VALID', 'message' => 'Complies with UBL 2.1 standards in line with ZATCA specifications']
                    ],
                    'warningMessages' => [],
                    'errorMessages' => [],
                    'status' => 'PASS'
                ],
                'reportingStatus' => 'REPORTED',
                'clearanceStatus' => null,
                'qrSellertStatus' => null,
                'qrBuyertStatus' => null
            ];
        } else {
            return [
                'status' => 'ERROR',
                'validationResults' => [
                    'infoMessages' => [],
                    'warningMessages' => [],
                    'errorMessages' => [
                        ['type' => 'ERROR', 'code' => 'missing_field', 'message' => 'Missing Buyer VAT Number']
                    ],
                    'status' => 'FAIL'
                ]
            ];
        }
    }

    /**
     * MOCK: Clear Standard Tax Invoice (B2B)
     */
    public function clearInvoice($invoiceHash, $xmlContent, $uuid) {
        sleep(2); // Simulate network latency
        
        $success = true;
        
        if ($success) {
            return [
                'status' => 'CLEARED',
                'validationResults' => [
                    'infoMessages' => [
                        ['type' => 'INFO', 'code' => 'XSD_ZATCA_VALID', 'message' => 'Complies with UBL 2.1 standards in line with ZATCA specifications']
                    ],
                    'warningMessages' => [],
                    'errorMessages' => [],
                    'status' => 'PASS'
                ],
                'clearanceStatus' => 'CLEARED',
                'clearedInvoice' => base64_encode($xmlContent) // ZATCA returns the cryptographic stamped XML
            ];
        } else {
             return [
                'status' => 'ERROR',
                'validationResults' => [
                    'infoMessages' => [],
                    'warningMessages' => [],
                    'errorMessages' => [
                        ['type' => 'ERROR', 'code' => 'invalid_hash', 'message' => 'The invoice hash does not match the XML content']
                    ],
                    'status' => 'FAIL'
                ]
            ];
        }
    }
}
?>
