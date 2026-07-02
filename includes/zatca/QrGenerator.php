<?php
// includes/zatca/QrGenerator.php

class ZATCA_QrGenerator {
    /**
     * Generates a ZATCA compliant Base64 TLV encoded string for the QR Code.
     *
     * @param string $sellerName
     * @param string $vatNumber
     * @param string $timestamp YYYY-MM-DDTHH:MM:SSZ
     * @param string $invoiceTotal
     * @param string $vatTotal
     * @param string $hash (Optional, base64)
     * @param string $signature (Optional, base64)
     * @param string $publicKey (Optional, base64)
     * @param string $certificateSignature (Optional, base64)
     * @return string Base64 encoded TLV string
     */
    public static function generateTlvBase64(
        $sellerName,
        $vatNumber,
        $timestamp,
        $invoiceTotal,
        $vatTotal,
        $hash = null,
        $signature = null,
        $publicKey = null,
        $certificateSignature = null
    ) {
        $tlv = self::toTlv(1, $sellerName) .
               self::toTlv(2, $vatNumber) .
               self::toTlv(3, $timestamp) .
               self::toTlv(4, $invoiceTotal) .
               self::toTlv(5, $vatTotal);
               
        if ($hash) {
            $tlv .= self::toTlv(6, $hash);
        }
        if ($signature) {
            $tlv .= self::toTlv(7, $signature);
        }
        if ($publicKey) {
            $tlv .= self::toTlv(8, $publicKey);
        }
        if ($certificateSignature) {
            $tlv .= self::toTlv(9, $certificateSignature);
        }

        return base64_encode($tlv);
    }

    /**
     * Converts a value to Tag-Length-Value format
     */
    private static function toTlv($tag, $value) {
        $valueStr = (string) $value;
        $len = strlen($valueStr);
        return chr($tag) . chr($len) . $valueStr;
    }
}
?>
