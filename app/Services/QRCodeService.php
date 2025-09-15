<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Event;

class QRCodeService
{
    /**
     * Structure des QR Codes selon EMVCO/AMA pour la billetterie
     */

    // IDs des objets de données (basés sur EMVCO)
    const PAYLOAD_FORMAT_INDICATOR = '00';
    const POINT_OF_INITIATION = '01';
    const TICKET_ACCOUNT_INFO = '35'; // Template principal ticket
    const MERCHANT_CATEGORY = '52';
    const TRANSACTION_CURRENCY = '53';
    const COUNTRY_CODE = '58';
    const MERCHANT_NAME = '59';
    const MERCHANT_CITY = '60';
    const ADDITIONAL_DATA = '62';
    const CRC = '63';

    // Template Informations Ticket (sous l'ID 35)
    const TICKET_UNIQUE_ID = '00';
    const EVENT_ID = '01';
    const BUYER_ID = '02';
    const TICKET_TYPE_ID = '03';
    const ISSUED_TIMESTAMP = '04';

    // Template Données Additionnelles (sous l'ID 62)
    const INVOICE_NUMBER = '01';
    const REFERENCE_ID = '05';
    const TERMINAL_ID = '07';
    const TRANSACTION_PURPOSE = '08';
    const TICKETING_TEMPLATE = '51';

    // Template Billetterie (sous l'ID 51)
    const GIMAC_IDENTIFIER = '00';
    const INTENT = '01';
    const SECURITY_HASH = '02';

    /**
     * Générer un QR Code sécurisé pour un billet
     */
    public function generateTicketQRCode(Ticket $ticket): string
    {
        $event = $ticket->event;
        $qrData = '';

        // 1. Payload Format Indicator (obligatoire en premier)
        $qrData .= $this->formatDataObject(self::PAYLOAD_FORMAT_INDICATOR, '01');

        // 2. Point of Initiation Method (12 = Dynamic QR)
        $qrData .= $this->formatDataObject(self::POINT_OF_INITIATION, '12');

        // 3. Template Informations Ticket (ID 35)
        $ticketInfo = $this->buildTicketAccountInfo($ticket);
        $qrData .= $this->formatDataObject(self::TICKET_ACCOUNT_INFO, $ticketInfo);

        // 4. Merchant Category Code (7999 = Événements/Divertissement)
        $qrData .= $this->formatDataObject(self::MERCHANT_CATEGORY, '7999');

        // 5. Transaction Currency (950 = XAF - Franc CFA CEMAC)
        $qrData .= $this->formatDataObject(self::TRANSACTION_CURRENCY, '950');

        // 6. Country Code (GA = Gabon)
        $qrData .= $this->formatDataObject(self::COUNTRY_CODE, 'GA');

        // 7. Merchant Name (Nom de l'événement)
        $merchantName = $this->truncateString($event->title, 25);
        $qrData .= $this->formatDataObject(self::MERCHANT_NAME, $merchantName);

        // 8. Merchant City (Ville de l'événement)
        $merchantCity = $this->truncateString($event->venue_city ?? 'LIBREVILLE', 15);
        $qrData .= $this->formatDataObject(self::MERCHANT_CITY, $merchantCity);

        // 9. Template Données Additionnelles (ID 62)
        $additionalData = $this->buildAdditionalDataTemplate($ticket);
        $qrData .= $this->formatDataObject(self::ADDITIONAL_DATA, $additionalData);

        // 10. CRC (obligatoire en dernier)
        $dataForCrc = $qrData . self::CRC . '04'; // Données + CRC ID + Length
        $crc = $this->calculateCRC($dataForCrc);
        $qrData .= self::CRC . '04' . $crc;

        return $qrData;
    }

    /**
     * Construire le template d'informations ticket (ID 35)
     */
    private function buildTicketAccountInfo(Ticket $ticket): string
    {
        $data = '';

        // Identifiant unique du ticket (hash sécurisé)
        $uniqueId = $this->generateSecureTicketId($ticket);
        $data .= $this->formatDataObject(self::TICKET_UNIQUE_ID, $uniqueId);

        // ID de l'événement
        $data .= $this->formatDataObject(self::EVENT_ID, (string) $ticket->event_id);

        // ID de l'acheteur (hashé pour la confidentialité)
        $buyerHash = substr(hash('sha256', $ticket->buyer_id . config('app.key')), 0, 12);
        $data .= $this->formatDataObject(self::BUYER_ID, $buyerHash);

        // ID du type de billet
        $data .= $this->formatDataObject(self::TICKET_TYPE_ID, (string) $ticket->ticket_type_id);

        // Timestamp d'émission (Unix timestamp)
        $timestamp = $ticket->issued_at ? $ticket->issued_at->timestamp : now()->timestamp;
        $data .= $this->formatDataObject(self::ISSUED_TIMESTAMP, (string) $timestamp);

        return $data;
    }

    /**
     * Construire le template de données additionnelles (ID 62)
     */
    private function buildAdditionalDataTemplate(Ticket $ticket): string
    {
        $data = '';

        // Numéro de référence du billet
        $data .= $this->formatDataObject(self::REFERENCE_ID, $ticket->code);

        // Purpose of Transaction
        $data .= $this->formatDataObject(self::TRANSACTION_PURPOSE, 'TICKET_VALIDATION');

        // Template spécifique billetterie (ID 51)
        $ticketingTemplate = $this->buildTicketingTemplate($ticket);
        $data .= $this->formatDataObject(self::TICKETING_TEMPLATE, $ticketingTemplate);

        return $data;
    }

    /**
     * Construire le template billetterie spécifique (ID 51)
     */
    private function buildTicketingTemplate(Ticket $ticket): string
    {
        $data = '';

        // Identifiant GIMAC (adapté pour la billetterie)
        $data .= $this->formatDataObject(self::GIMAC_IDENTIFIER, 'org.ticketing.secure');

        // Intent (type de transaction)
        $data .= $this->formatDataObject(self::INTENT, 'ticket_validation');

        // Hash de sécurité (pour vérification d'intégrité)
        $securityHash = $this->generateSecurityHash($ticket);
        $data .= $this->formatDataObject(self::SECURITY_HASH, $securityHash);

        return $data;
    }

    /**
     * Générer un ID unique sécurisé pour le billet
     */
    private function generateSecureTicketId(Ticket $ticket): string
    {
        $data = implode('|', [
            $ticket->id,
            $ticket->code,
            $ticket->event_id,
            $ticket->issued_at?->timestamp ?? now()->timestamp,
            config('app.key')
        ]);

        return substr(hash('sha256', $data), 0, 16); // 16 caractères
    }

    /**
     * Générer un hash de sécurité pour la vérification
     */
    private function generateSecurityHash(Ticket $ticket): string
    {
        $data = implode(':', [
            $ticket->id,
            $ticket->code,
            $ticket->status,
            $ticket->event_id,
            $ticket->buyer_id,
            date('Y-m-d'), // Change chaque jour pour plus de sécurité
            config('app.key')
        ]);

        return substr(hash('sha256', $data), 0, 20); // 20 caractères
    }

    /**
     * Formatter un objet de données (ID/Length/Value)
     */
    private function formatDataObject(string $id, string $value): string
    {
        $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $length . $value;
    }

    /**
     * Calculer le CRC-16 selon ISO/IEC 13239
     */
    private function calculateCRC(string $data): string
    {
        $crc = 0xFFFF; // Valeur initiale
        $polynomial = 0x1021; // Polynôme

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Tronquer une chaîne à la longueur spécifiée
     */
    private function truncateString(string $string, int $maxLength): string
    {
        return substr($string, 0, $maxLength);
    }

    /**
     * Décoder un QR Code de billet (pour la validation)
     */
    public function decodeTicketQRCode(string $qrData): array
    {
        $result = [
            'valid' => false,
            'ticket_id' => null,
            'event_id' => null,
            'security_hash' => null,
            'issued_timestamp' => null,
            'error' => null
        ];

        try {
            $offset = 0;
            $parsedData = [];

            // Parser tous les objets de données
            while ($offset < strlen($qrData) - 4) { // -4 pour le CRC final
                if ($offset + 4 > strlen($qrData)) break;

                $id = substr($qrData, $offset, 2);
                $length = (int) substr($qrData, $offset + 2, 2);
                
                if ($offset + 4 + $length > strlen($qrData)) break;
                
                $value = substr($qrData, $offset + 4, $length);
                $parsedData[$id] = $value;
                
                $offset += 4 + $length;
            }

            // Vérifier le CRC
            $expectedCrc = substr($qrData, -4);
            $dataForCrc = substr($qrData, 0, -4) . self::CRC . '04';
            $calculatedCrc = $this->calculateCRC($dataForCrc);

            if ($expectedCrc !== $calculatedCrc) {
                $result['error'] = 'CRC_MISMATCH';
                return $result;
            }

            // Extraire les informations du ticket depuis le template ID 35
            if (isset($parsedData[self::TICKET_ACCOUNT_INFO])) {
                $ticketInfo = $this->parseTemplate($parsedData[self::TICKET_ACCOUNT_INFO]);
                
                $result['ticket_unique_id'] = $ticketInfo[self::TICKET_UNIQUE_ID] ?? null;
                $result['event_id'] = $ticketInfo[self::EVENT_ID] ?? null;
                $result['ticket_type_id'] = $ticketInfo[self::TICKET_TYPE_ID] ?? null;
                $result['issued_timestamp'] = $ticketInfo[self::ISSUED_TIMESTAMP] ?? null;
            }

            // Extraire les données additionnelles depuis le template ID 62
            if (isset($parsedData[self::ADDITIONAL_DATA])) {
                $additionalData = $this->parseTemplate($parsedData[self::ADDITIONAL_DATA]);
                
                $result['reference_id'] = $additionalData[self::REFERENCE_ID] ?? null;

                // Parser le template billetterie (ID 51)
                if (isset($additionalData[self::TICKETING_TEMPLATE])) {
                    $ticketingData = $this->parseTemplate($additionalData[self::TICKETING_TEMPLATE]);
                    $result['security_hash'] = $ticketingData[self::SECURITY_HASH] ?? null;
                    $result['intent'] = $ticketingData[self::INTENT] ?? null;
                }
            }

            $result['valid'] = true;
            $result['parsed_data'] = $parsedData;

        } catch (\Exception $e) {
            $result['error'] = 'PARSE_ERROR: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Parser un template (structure ID/Length/Value imbriquée)
     */
    private function parseTemplate(string $templateData): array
    {
        $result = [];
        $offset = 0;

        while ($offset < strlen($templateData)) {
            if ($offset + 4 > strlen($templateData)) break;

            $id = substr($templateData, $offset, 2);
            $length = (int) substr($templateData, $offset + 2, 2);
            
            if ($offset + 4 + $length > strlen($templateData)) break;
            
            $value = substr($templateData, $offset + 4, $length);
            $result[$id] = $value;
            
            $offset += 4 + $length;
        }

        return $result;
    }

    /**
     * Valider un billet via son QR Code décodé
     */
    public function validateTicketFromQRCode(string $qrCode): array
    {
        $decoded = $this->decodeTicketQRCode($qrCode);
        
        if (!$decoded['valid']) {
            return [
                'valid' => false,
                'message' => 'QR Code invalide ou corrompu',
                'error' => $decoded['error']
            ];
        }

        // Trouver le billet par sa référence
        if (!$decoded['reference_id']) {
            return [
                'valid' => false,
                'message' => 'Référence de billet manquante dans le QR Code'
            ];
        }

        $ticket = Ticket::where('code', $decoded['reference_id'])->first();
        
        if (!$ticket) {
            return [
                'valid' => false,
                'message' => 'Billet non trouvé dans la base de données'
            ];
        }

        // Vérifier le hash de sécurité
        $expectedHash = $this->generateSecurityHash($ticket);
        if ($decoded['security_hash'] !== $expectedHash) {
            return [
                'valid' => false,
                'message' => 'Hash de sécurité invalide - possible contrefaçon',
                'ticket' => $ticket
            ];
        }

        // Vérifications métier
        if ($ticket->status === 'used') {
            return [
                'valid' => false,
                'message' => 'Ce billet a déjà été utilisé',
                'ticket' => $ticket
            ];
        }

        if ($ticket->status !== 'issued') {
            return [
                'valid' => false,
                'message' => 'Statut de billet invalide: ' . $ticket->status,
                'ticket' => $ticket
            ];
        }

        return [
            'valid' => true,
            'message' => 'Billet valide',
            'ticket' => $ticket,
            'decoded_data' => $decoded
        ];
    }
}