<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Services\QRCodeService;

class TestEmvcoScan extends Command
{
    protected $signature = 'qr:test-scan';
    protected $description = 'Test le scan d\'un QR Code EMVCO généré';

    public function handle()
    {
        $this->info('🔍 Test de scan EMVCO/AMA QR Code');
        $this->newLine();

        // 1. Créer un billet réel pour le test
        $this->info('📝 Création d\'un billet de test...');
        $ticket = Ticket::create([
            'code' => 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'order_id' => 1,
            'event_id' => 999,
            'ticket_type_id' => 999,
            'buyer_id' => 1,
            'status' => 'issued',
            'issued_at' => now(),
        ]);
        
        $this->line("✅ Billet créé: {$ticket->code}");

        // 2. Générer le QR Code EMVCO
        $this->info('🔐 Génération du QR Code EMVCO...');
        $qrService = new QRCodeService();
        
        // Créer des relations mockées
        $event = new \App\Models\Event();
        $event->id = 999;
        $event->title = 'Concert Test Gabon';
        $event->venue_city = 'Libreville';
        
        $ticketType = new \App\Models\TicketType();
        $ticketType->id = 999;
        $ticketType->name = 'VIP';
        
        $buyer = \App\Models\User::find(1);
        
        $ticket->setRelation('event', $event);
        $ticket->setRelation('ticketType', $ticketType);
        $ticket->setRelation('buyer', $buyer);
        
        $qrCode = $qrService->generateTicketQRCode($ticket);
        $this->line("✅ QR Code généré (longueur: " . strlen($qrCode) . ")");
        
        // 3. Afficher le QR Code pour copie
        $this->newLine();
        $this->info('📋 QR Code EMVCO complet:');
        $this->line($qrCode);
        $this->newLine();

        // 4. Décoder le QR Code
        $this->info('🔍 Décodage du QR Code...');
        $decoded = $qrService->decodeTicketQRCode($qrCode);
        
        if ($decoded['valid']) {
            $this->info('✅ QR Code décodé avec succès!');
            
            $this->table(['Champ', 'Valeur'], [
                ['Format', 'EMVCO/AMA Compatible'],
                ['Référence Ticket', $decoded['reference_id'] ?? 'N/A'],
                ['Event ID', $decoded['event_id'] ?? 'N/A'],
                ['Ticket Type ID', $decoded['ticket_type_id'] ?? 'N/A'],
                ['Security Hash', substr($decoded['security_hash'] ?? 'N/A', 0, 20) . '...'],
                ['Timestamp', isset($decoded['issued_timestamp']) ? date('Y-m-d H:i:s', $decoded['issued_timestamp']) : 'N/A'],
            ]);
        } else {
            $this->error('❌ Erreur de décodage: ' . ($decoded['error'] ?? 'Inconnue'));
        }

        // 5. Valider le billet
        $this->newLine();
        $this->info('✅ Validation du billet via QR Code...');
        $validation = $qrService->validateTicketFromQRCode($qrCode);
        
        if ($validation['valid']) {
            $this->info('🎫 Billet VALIDE!');
            $this->line("Code du billet: {$validation['ticket']->code}");
            $this->line("Statut: {$validation['ticket']->status}");
        } else {
            $this->error('❌ Validation échouée: ' . $validation['message']);
        }

        // 6. Instructions pour tester avec Flutter
        $this->newLine();
        $this->info('📱 Test avec l\'app Flutter:');
        $this->line('1. Copiez le QR code ci-dessus');
        $this->line('2. Générez une image QR (via un générateur en ligne)');
        $this->line('3. Scannez avec l\'app mobile Flutter');
        $this->line('4. L\'app devrait:');
        $this->line('   - Détecter le format EMVCO');
        $this->line('   - Extraire le code ticket: ' . $ticket->code);
        $this->line('   - Valider via l\'API avec le code extrait');
        $this->line('   - Afficher les infos de sécurité EMVCO');

        $this->newLine();
        $this->info('🎉 Test terminé!');
        
        return 0;
    }
}