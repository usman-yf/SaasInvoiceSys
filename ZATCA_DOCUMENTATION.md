# ZATCA Phase 2 E-Invoicing: Configuration & Onboarding Guide

This guide explains exactly how to configure your SaaS application to connect with the Saudi Arabia ZATCA Phase 2 (Generation and Integration) E-Invoicing systems. 

By following these steps, you will obtain the necessary **Public Certificate (CSID)** and **API Secret** required to authenticate with ZATCA's Core APIs.

---

## Prerequisites

Before starting, ensure you have:
1. **Access to ZATCA Fatoora Portal:** You need your company's ERAD portal credentials.
   - *Sandbox/Developer Portal:* `https://sandbox.zatca.gov.sa/`
   - *Simulation Portal:* `https://simulation.zatca.gov.sa/`
   - *Production Portal:* `https://fatoora.zatca.gov.sa/`
2. **Company Information:** Your 15-digit VAT number, Commercial Registration (CR) number, and full National Address.

---

## Step 1: Prepare Your SaaS Application

Your local system is responsible for generating the Cryptographic Private Key and the Certificate Signing Request (CSR). ZATCA does **not** generate the private key for you.

1. Log in to your SaaS Invoice System as an **Admin**.
2. Navigate to **Settings** from the main menu.
3. Under **Company Details**, ensure the following fields are accurately filled out:
   - **VAT Number:** Must be exactly 15 digits (e.g., `300000000000003`).
   - **CR Number:** Your Commercial Registration number.
   - **National Address:** Building Number, Street, District, City, and Postal Code.
4. Click **Save Localization** to save your company details.

---

## Step 2: Generate the Private Key & CSR

Once your company details are saved, you must generate your cryptographic keys.

1. Still in the **Settings** menu, scroll down to the **ZATCA Configuration & Cryptographic Keys** section.
2. Select your desired **Environment** (e.g., Sandbox, Simulation, or Production) from the top-right dropdown.
3. Click the dark button labeled **Auto-Generate Key & CSR** located at the bottom-left of the card.
4. The system will automatically generate two pieces of data:
   - **Private Key (ECDSA secp256k1):** Keep this absolutely secure. It never leaves your server.
   - **CSR (Certificate Signing Request):** This is what you will provide to ZATCA.

> [!CAUTION]
> Never share your Private Key with anyone, not even ZATCA. Only the CSR is meant to be shared.

---

## Step 3: Obtain the OTP from ZATCA Fatoora Portal

ZATCA requires an OTP (One-Time Password) to link your CSR to your taxpayer account.

1. Log in to the corresponding ZATCA Fatoora Portal (Sandbox, Simulation, or Production) using your ERAD credentials.
2. Navigate to the **Onboard New Device / Solution** section.
3. Click on **Generate OTP**.
4. ZATCA will display a temporary OTP (usually valid for a short period of time). **Copy this OTP**.

---

## Step 4: Submit the CSR and Get the Certificate (CSID)

*Note: In the official ZATCA Developer Portal, you usually submit your CSR along with the OTP via an API call (the Compliance/Onboarding API). For manual onboarding via the web portal:*

1. In the ZATCA portal, follow the prompts for **Manual CSR Upload** or **Device Registration**.
2. **Copy the contents** of the CSR text area from your SaaS Settings page (it begins with `-----BEGIN CERTIFICATE REQUEST-----`).
3. Paste the CSR into the ZATCA portal and provide the OTP when prompted.
4. Submit the request.
5. If successful, ZATCA will instantly return two highly important credentials:
   - **Public Certificate (CSID / X.509 Certificate)**
   - **API Secret (Binary Security Token / CCSID)**

---

## Step 5: Save Credentials in Your SaaS

1. Go back to your SaaS **Settings** page.
2. Paste the returned **Public Certificate** into the `Public Certificate (X.509)` text area. It should begin with `-----BEGIN CERTIFICATE-----`.
3. Paste the returned **API Secret** into the `API Secret` text area.
4. Click the purple **Save ZATCA Configuration** button.

---

## 🚀 You're All Set!

Your system is now fully onboarded and securely linked to ZATCA! 

When you navigate to **Invoices** -> **View Invoice** and click **Submit to ZATCA**, your SaaS will:
1. Generate the UBL 2.1 XML.
2. Hash the XML using SHA-256.
3. Sign the Hash natively using your Private Key.
4. Inject your Public Certificate into the XML.
5. Authenticate with ZATCA's Core API using your API Secret.
6. Transmit the invoice and record the Cleared/Reported status in your system.
