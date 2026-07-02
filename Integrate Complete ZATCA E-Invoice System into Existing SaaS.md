You are an Elite Senior Software Architect, Senior PHP Engineer, ZATCA E-Invoice Expert, Enterprise SaaS Architect, Security Engineer, Backend Engineer, Database Architect, and UI/UX Engineer.

Your task is NOT to rebuild the application.

Your task is to integrate a complete production-ready Saudi Arabia ZATCA Phase 2 (Generation + Integration Phase) E-Invoicing System into my existing Premium SaaS Invoice Management System.

======================================================
EXISTING PROJECT
======================================================

The application is already completed.

Technology:

• PHP 8+
• MySQL
• HTML5
• TailwindCSS
• Vanilla JavaScript
• AJAX
• Responsive SaaS UI

The project already contains:

Dashboard

Invoice Management

Customers

Products

Payments

Reports

Analytics

Settings

Authentication

Roles

Permissions

Notifications

Modern UI

Premium Design System

Do NOT redesign the application.

Use exactly the same UI/UX language.

Every new screen must match the existing design system.

Everything must look native.

No page should look different.

======================================================
CRITICAL RULES
======================================================

DO NOT rebuild.

DO NOT replace existing invoice system.

DO NOT remove features.

DO NOT change existing CRUD.

DO NOT change routes.

DO NOT rename files.

DO NOT rename IDs.

DO NOT rename forms.

DO NOT break backend.

DO NOT modify existing business logic unless required for ZATCA integration.

Instead,

Extend the application.

======================================================
GOAL
======================================================

Transform the existing Invoice Management System into a complete Saudi ZATCA compliant invoicing platform.

The project must support

Phase 1

Phase 2

Generation

Integration

Reporting

Clearance

QR Codes

Digital Signature

XML

UBL 2.1

PIH

ICV

UUID

Cryptographic Stamp

Production Ready

======================================================
FEATURES TO ADD
======================================================

1. Company ZATCA Configuration

Create a new module

Settings

→ ZATCA

Include

Company Information

VAT Number

CR Number

Business Name

Arabic Name

Building Number

Street

District

City

Postal Code

Country

Branch

Device UUID

Environment

Production

Simulation

Sandbox

Certificate Information

Private Key

Public Certificate

CSR

Binary Security Token

Secret

OTP

Certificate Expiry

Renew Certificate

Connection Status

Validate Configuration

======================================================

2. CSR Generation

Automatically generate

Private Key

CSR

OpenSSL Keys

Download CSR

Upload CSR

Receive Certificate

Store Securely

Rotate Certificate

======================================================

3. Invoice XML Generator

Generate UBL 2.1 XML

Include every required ZATCA tag

Invoice

Supplier

Customer

Tax

Items

Discount

Charges

Totals

VAT Breakdown

UUID

PIH

ICV

Cryptographic Stamp

Previous Invoice Hash

Digital Signature

Invoice Hash

======================================================

4. QR Code Generator

Generate TLV encoded QR

Seller Name

VAT Number

Timestamp

Invoice Total

VAT Total

Signature

Display QR

Print QR

Download QR

======================================================

5. Digital Signature

Implement

ECDSA

SHA256

XML Signature

Canonicalization

Signed Properties

Certificate Validation

======================================================

6. Invoice Clearance

Support

Standard Tax Invoice

Clearance API

Receive Cleared Invoice

Store Cleared XML

Store Response

Store Clearance Status

======================================================

7. Reporting API

Support Simplified Tax Invoice

Automatic Reporting

Retry Failed Reports

Offline Queue

Success Log

======================================================

8. API Integration

Sandbox

Simulation

Production

Retry Logic

Queue

Timeout

Rate Limiting

Logging

Error Handling

======================================================

9. Invoice Status

Each invoice must show

Draft

Generated XML

Signed

Pending Clearance

Cleared

Reported

Rejected

Cancelled

Error

Display beautiful badges.

======================================================

10. Invoice Details Page

Add new premium tabs

Overview

PDF

XML

QR

ZATCA Response

Digital Signature

Validation

Timeline

Logs

======================================================

11. Invoice Actions

Generate XML

Generate QR

Sign Invoice

Validate

Submit

Report

Clear

Download XML

Download Signed XML

Download QR

View API Response

Retry

======================================================

12. ZATCA Dashboard Widgets

Today's Generated

Today's Cleared

Today's Reported

Pending

Failed

Rejected

Certificates

API Health

Recent Responses

Timeline

======================================================

13. Logs

Create

API Logs

Validation Logs

Certificate Logs

Error Logs

Submission Logs

Retry Logs

======================================================

14. Validation Engine

Validate

VAT

IBAN

Invoice Number

UUID

Totals

XML Schema

Business Rules

Warnings

Errors

======================================================

15. Background Processing

Automatic Retry

Queue

Scheduler

Sync Status

Background XML Generation

Background Submission

======================================================

16. Notification System

Toast

Success

Error

Certificate Expiry

API Failure

Rejected Invoice

Pending Submission

======================================================

17. Reports

Daily

Monthly

VAT Summary

Cleared

Reported

Rejected

Pending

XML Export

CSV Export

Excel Export

PDF Export

======================================================

18. Security

Encrypt Certificates

Encrypt Keys

Environment Variables

CSRF

Prepared Statements

Audit Logs

Permission Checks

Rate Limiting

======================================================

DATABASE

Create only additional tables.

Never modify existing invoice tables unless absolutely necessary.

Use migrations.

======================================================

API LAYER

Create a reusable service layer.

Separate

Certificate Service

XML Generator

QR Generator

Digital Signature

Hash Generator

API Client

Validation Service

Queue Service

Logging Service

======================================================

FILE STRUCTURE

Organize all new code under

/modules/zatca

/services/zatca

/helpers/zatca

/storage/certificates

/storage/xml

/storage/signed

/storage/logs

/storage/qr

without affecting existing modules.

======================================================

UI REQUIREMENTS

Follow EXACTLY the existing Premium SaaS design system.

Reuse

Cards

Buttons

Tables

Forms

Typography

Spacing

Icons

Modals

Toast Notifications

Animations

Dark Mode

Light Mode

Responsive Layout

Every ZATCA page must feel like it was part of the original application.

======================================================

OUTPUT REQUIREMENTS

Implement the integration incrementally.

Do not regenerate existing code.

Only create or modify files necessary for ZATCA integration.

Before editing any file, analyze dependencies.

Maintain backward compatibility.

Preserve 100% existing functionality.

Deliver production-ready, secure, maintainable code with comments where necessary.

The final system must be fully compliant with Saudi ZATCA Phase 2 requirements while preserving the application's existing architecture, premium UI, and business logic.