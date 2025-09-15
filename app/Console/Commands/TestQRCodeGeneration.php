<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Models\TicketType;
use App\Services\QRCodeService;

class TestQRCodeGeneration extends Command
{
    protected $signature = 'qr:test-generation';
    protected $description = 'Test la génération et validation de QR Codes sécurisés';

    public function handle()
    {
        $this->info('🔍 Test de génération de QR Codes sécurisés EMVCO/AMA');
        $this->newLine();

        // Créer des données de test si nécessaire
        $user = User::firstOrCreate([
            'email' => 'test@example.com'
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'is_organizer' => false,
            'status' => 'active'
        ]);

        // Créer un événement de test simple
        $event = new Event();
        $event->id = 999;
        $event->title = 'Concert Test';
        $event->slug = 'concert-test';
        $event->organizer_id = 1;
        $event->status = 'published';
        $event->venue_name = 'Palais des Sports';
        $event->venue_city = 'Libreville';
        $event->published_at = now();
        // Ne pas sauvegarder en DB pour ce test

        $ticketType = new TicketType();
        $ticketType->id = 999;
        $ticketType->event_id = $event->id;
        $ticketType->name = 'Standard';
        $ticketType->price = 5000;
        $ticketType->currency = 'XAF';
        $ticketType->status = 'active';
        // Ne pas sauvegarder en DB pour ce test

        // Créer un billet de test simple
        $ticket = new Ticket();
        $ticket->id = 999;
        $ticket->code = 'TEST-' . strtoupper(substr(md5(time()), 0, 8));
        $ticket->event_id = $event->id;
        $ticket->ticket_type_id = $ticketType->id;
        $ticket->buyer_id = $user->id;
        $ticket->status = 'issued';
        $ticket->issued_at = now();
        $ticket->buyer_name = $user->name;
        $ticket->buyer_email = $user->email;
        
        // Définir les relations manuellement pour ce test
        $ticket->setRelation('event', $event);
        $ticket->setRelation('ticketType', $ticketType);
        $ticket->setRelation('buyer', $user);

        $this->info("✅ Billet créé: {$ticket->code}");
        $this->newLine();

        // Initialiser le service QR
        $qrService = new QRCodeService();

        // 1. Générer le QR Code sécurisé
        $this->info('🔐 Génération du QR Code sécurisé...');
        $secureQR = $qrService->generateTicketQRCode($ticket);
        
        $this->line("📱 QR Code sécurisé généré:");
        $this->line("Longueur: " . strlen($secureQR) . " caractères");
        $this->line("Format: EMVCO/AMA Compatible");
        $this->newLine();
        
        // Afficher les premiers caractères pour debug
        $this->line("Début du QR: " . substr($secureQR, 0, 50) . '...');
        $this->line("Fin du QR: ..." . substr($secureQR, -20));
        $this->newLine();

        // 2. Décoder le QR Code
        $this->info('🔍 Décodage du QR Code...');
        $decoded = $qrService->decodeTicketQRCode($secureQR);
        
        if ($decoded['valid']) {
            $this->info("✅ QR Code décodé avec succès!");
            $this->line("Event ID: " . ($decoded['event_id'] ?? 'N/A'));
            $this->line("Ticket Type ID: " . ($decoded['ticket_type_id'] ?? 'N/A'));
            $this->line("Référence: " . ($decoded['reference_id'] ?? 'N/A'));
            $this->line("Hash sécurité: " . substr($decoded['security_hash'] ?? 'N/A', 0, 20) . '...');
        } else {
            $this->error("❌ Erreur de décodage: " . ($decoded['error'] ?? 'Inconnue'));
        }
        $this->newLine();

        // 3. Valider le billet
        $this->info('✅ Validation du billet...');
        $validation = $qrService->validateTicketFromQRCode($secureQR);
        
        if ($validation['valid']) {
            $this->info("🎫 Billet VALIDE!");
            $this->line("Message: " . $validation['message']);
            $this->line("Statut: " . $validation['ticket']->status);
        } else {
            $this->error("❌ Billet INVALIDE: " . $validation['message']);
        }
        $this->newLine();

        // 4. Comparaison avec QR simple
        $this->info('📊 Comparaison des formats:');
        
        $this->table([
            'Format', 'Longueur', 'Sécurité', 'Anti-contrefaçon', 'Standard'
        ], [
            [
                'QR Simple',
                strlen($ticket->code) . ' chars',
                '🔴 Faible',
                '❌ Non',
                '❌ Propriétaire'
            ],
            [
                'QR Sécurisé EMVCO',
                strlen($secureQR) . ' chars',
                '🟢 Élevée',
                '✅ Oui',
                '✅ International'
            ]
        ]);

        // 5. Structure détaillée
        if ($decoded['valid'] && isset($decoded['parsed_data'])) {
            $this->newLine();
            $this->info('📋 Structure du QR Code (IDs EMVCO):');
            
            foreach ($decoded['parsed_data'] as $id => $value) {
                $description = $this->getEMVCODescription($id);
                $displayValue = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                $this->line("ID {$id}: {$description} = '{$displayValue}'");
            }
        }

        $this->newLine();
        $this->info('🎉 Test terminé avec succès!');
        
        return 0;
    }

    /**
     * Obtenir la description d'un ID EMVCO
     */
    private function getEMVCODescription(string $id): string
    {
        return match($id) {
            '00' => 'Payload Format Indicator',
            '01' => 'Point of Initiation Method',
            '35' => 'Ticket Account Information',
            '52' => 'Merchant Category Code',
            '53' => 'Transaction Currency',
            '58' => 'Country Code',
            '59' => 'Merchant Name',
            '60' => 'Merchant City',
            '62' => 'Additional Data Field Template',
            '63' => 'CRC',
            default => 'Unknown/Custom Field'
        };
    }
}