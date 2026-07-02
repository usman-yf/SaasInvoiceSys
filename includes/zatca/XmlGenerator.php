<?php
// includes/zatca/XmlGenerator.php

class ZATCA_XmlGenerator {
    
    /**
     * Generates a UBL 2.1 ZATCA-compliant XML for an invoice.
     */
    public static function generateInvoiceXml($invoice, $items, $company, $customer, $isSimplified = true) {
        $invoiceTypeCode = $isSimplified ? '388' : '388'; // 388 is standard Tax Invoice
        $subType = $isSimplified ? '0200000' : '0100000'; 
        
        $issueDate = date('Y-m-d', strtotime($invoice['date']));
        $issueTime = date('H:i:s', strtotime($invoice['date'])); 
        // For simplicity assuming the date contains time or using current time. Ideally invoice should have a proper timestamp.
        
        $uuid = $invoice['uuid'] ?: 'UNKNOWN-UUID';
        $pih = $invoice['previous_invoice_hash'] ?: 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjZTllYThiM2UzYmMyNWY5ZjQ4OTI3MGE1ZWVjYzQzYTI3OQ=='; // Default base64 hash if first
        
        $currency = "SAR";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
         xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <!-- Extension for Cryptographic Stamp (UBLExtensions) will be injected here during signing -->
    <cbc:ProfileID>reporting:1.0</cbc:ProfileID>
    <cbc:ID>' . htmlspecialchars($invoice['invoice_no']) . '</cbc:ID>
    <cbc:UUID>' . $uuid . '</cbc:UUID>
    <cbc:IssueDate>' . $issueDate . '</cbc:IssueDate>
    <cbc:IssueTime>' . $issueTime . '</cbc:IssueTime>
    <cbc:InvoiceTypeCode name="' . $subType . '">' . $invoiceTypeCode . '</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>' . $currency . '</cbc:DocumentCurrencyCode>
    <cbc:TaxCurrencyCode>' . $currency . '</cbc:TaxCurrencyCode>
    
    <!-- Billing Reference (PIH) -->
    <cac:AdditionalDocumentReference>
        <cbc:ID>PIH</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">' . $pih . '</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    
    <!-- Supplier -->
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="CRN">' . htmlspecialchars($company['company_cr']) . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
                <cbc:StreetName>' . htmlspecialchars($company['company_street']) . '</cbc:StreetName>
                <cbc:BuildingNumber>' . htmlspecialchars($company['company_building']) . '</cbc:BuildingNumber>
                <cbc:CityName>' . htmlspecialchars($company['company_city']) . '</cbc:CityName>
                <cbc:PostalZone>' . htmlspecialchars($company['company_postal']) . '</cbc:PostalZone>
                <cbc:CountrySubentity>' . htmlspecialchars($company['company_district']) . '</cbc:CountrySubentity>
                <cac:Country>
                    <cbc:IdentificationCode>' . htmlspecialchars($company['company_country']) . '</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>' . htmlspecialchars($company['company_vat']) . '</cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>' . htmlspecialchars($company['company_name']) . '</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    
    <!-- Customer -->
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PostalAddress>
                <cbc:StreetName>' . htmlspecialchars($customer['street'] ?? '') . '</cbc:StreetName>
                <cbc:BuildingNumber>' . htmlspecialchars($customer['building_no'] ?? '') . '</cbc:BuildingNumber>
                <cbc:CityName>' . htmlspecialchars($customer['city'] ?? '') . '</cbc:CityName>
                <cbc:PostalZone>' . htmlspecialchars($customer['postal_code'] ?? '') . '</cbc:PostalZone>
                <cbc:CountrySubentity>' . htmlspecialchars($customer['district'] ?? '') . '</cbc:CountrySubentity>
                <cac:Country>
                    <cbc:IdentificationCode>' . htmlspecialchars($customer['country'] ?? 'SA') . '</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>' . htmlspecialchars($customer['vat_number'] ?? '') . '</cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>' . htmlspecialchars($customer['name']) . '</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>
    
    <!-- Tax Totals -->
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="' . $currency . '">' . number_format($invoice['tax'], 2, '.', '') . '</cbc:TaxAmount>
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="' . $currency . '">' . number_format($invoice['subtotal'], 2, '.', '') . '</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="' . $currency . '">' . number_format($invoice['tax'], 2, '.', '') . '</cbc:TaxAmount>
            <cac:TaxCategory>
                <cbc:ID>S</cbc:ID>
                <cbc:Percent>15.00</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
    </cac:TaxTotal>
    
    <!-- Legal Monetary Totals -->
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="' . $currency . '">' . number_format($invoice['subtotal'], 2, '.', '') . '</cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="' . $currency . '">' . number_format($invoice['subtotal'], 2, '.', '') . '</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="' . $currency . '">' . number_format($invoice['total'], 2, '.', '') . '</cbc:TaxInclusiveAmount>
        <cbc:AllowanceTotalAmount currencyID="' . $currency . '">' . number_format($invoice['discount'], 2, '.', '') . '</cbc:AllowanceTotalAmount>
        <cbc:PayableAmount currencyID="' . $currency . '">' . number_format($invoice['total'], 2, '.', '') . '</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
';

        // Add Line Items
        $lineId = 1;
        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['price'];
            $itemVat = $itemTotal * 0.15; // Assuming 15% Standard VAT
            $xml .= '
    <cac:InvoiceLine>
        <cbc:ID>' . $lineId . '</cbc:ID>
        <cbc:InvoicedQuantity unitCode="PCE">' . number_format($item['quantity'], 2, '.', '') . '</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="' . $currency . '">' . number_format($itemTotal, 2, '.', '') . '</cbc:LineExtensionAmount>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="' . $currency . '">' . number_format($itemVat, 2, '.', '') . '</cbc:TaxAmount>
            <cac:RoundingAmount currencyID="' . $currency . '">' . number_format($itemTotal + $itemVat, 2, '.', '') . '</cac:RoundingAmount>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Name>' . htmlspecialchars($item['name']) . '</cbc:Name>
            <cac:ClassifiedTaxCategory>
                <cbc:ID>S</cbc:ID>
                <cbc:Percent>15.00</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:ClassifiedTaxCategory>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="' . $currency . '">' . number_format($item['price'], 2, '.', '') . '</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>';
            $lineId++;
        }

        $xml .= '
</Invoice>';

        return $xml;
    }

    /**
     * Inject Signature XML into the main XML
     */
    public static function injectSignature($xml, $certBase64, $signatureBase64, $qrBase64, $hashBase64) {
        $ext = '
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionURI>urn:oasis:names:specification:ubl:dsig:enveloped:xades</ext:ExtensionURI>
            <ext:ExtensionContent>
                <sig:UBLDocumentSignatures xmlns:sig="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2" xmlns:sac="urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2" xmlns:sbc="urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2">
                    <sac:SignatureInformation>
                        <cbc:ID>urn:oasis:names:specification:ubl:signature:1</cbc:ID>
                        <sbc:ReferencedSignatureID>urn:oasis:names:specification:ubl:signature:Invoice</sbc:ReferencedSignatureID>
                        <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="signature">
                            <ds:SignedInfo>
                                <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2006/12/xml-c14n11"/>
                                <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256"/>
                                <ds:Reference Id="invoiceSignedData" URI="">
                                    <ds:Transforms>
                                        <ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
                                            <ds:XPath>not(//ancestor-or-self::ext:UBLExtensions)</ds:XPath>
                                        </ds:Transform>
                                        <ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
                                            <ds:XPath>not(//ancestor-or-self::cac:Signature)</ds:XPath>
                                        </ds:Transform>
                                        <ds:Transform Algorithm="http://www.w3.org/2006/12/xml-c14n11"/>
                                    </ds:Transforms>
                                    <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                                    <ds:DigestValue>' . $hashBase64 . '</ds:DigestValue>
                                </ds:Reference>
                            </ds:SignedInfo>
                            <ds:SignatureValue>' . $signatureBase64 . '</ds:SignatureValue>
                            <ds:KeyInfo>
                                <ds:X509Data>
                                    <ds:X509Certificate>' . $certBase64 . '</ds:X509Certificate>
                                </ds:X509Data>
                            </ds:KeyInfo>
                        </ds:Signature>
                    </sac:SignatureInformation>
                </sig:UBLDocumentSignatures>
            </ext:ExtensionContent>
        </ext:UBLExtension>
    </ext:UBLExtensions>';
    
        // Inject right after namespace declarations (first child of Invoice)
        return preg_replace('/(<Invoice[^>]*>)/', '$1' . $ext, $xml, 1);
    }
}
?>
