<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSyncCommand extends Command
{
    protected $signature = 'telegram:sync 
                            {--webhook= : Set webhook URL (e.g. https://spilms.tech/api/telegram/webhook)}
                            {--secret= : Custom webhook secret token}
                            {--delete-webhook : Delete existing webhook and clear pending updates}
                            {--audit-group : List and audit all administrators in the configured Telegram Group}
                            {--ban-user= : Ban a specific Telegram User ID from the group}
                            {--forensics : Display all intercepted security incidents and digital footprints}';

    protected $description = 'Synchronize Telegram Bot Profile, Commands, Webhook, Audit Group and View Forensics';

    public function handle(TelegramService $telegramService)
    {
        $this->info('🚀 Starting Telegram Bot Synchronization...');

        $token = $telegramService->getBotToken();
        if (empty($token)) {
            $this->error('❌ TELEGRAM_BOT_TOKEN is not configured in .env or config/services.php.');
            return self::FAILURE;
        }

        // 1. Check getMe
        try {
            $meRes = Http::withoutVerifying()->timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");
            if ($meRes->successful() && $meRes->json('ok')) {
                $bot = $meRes->json('result');
                $this->info("✅ Bot Authenticated: @{$bot['username']} (ID: {$bot['id']})");
            } else {
                $this->error("❌ Invalid Token or Telegram API Error: " . $meRes->body());
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error("❌ Connection failed: " . $e->getMessage());
            return self::FAILURE;
        }

        // 2. Sync Profile & Slash Commands & Menu Button
        $this->info('🔄 Synchronizing Bot Name, Descriptions, Commands & Menu...');
        $syncOk = $telegramService->syncBotCommandsAndMenu();
        if ($syncOk) {
            $this->info('✅ Bot Profile & Commands successfully updated on Telegram!');
        } else {
            $this->warn('⚠️ Profile sync encountered an issue (check logs).');
        }

        // 3. Webhook handling
        if ($this->option('delete-webhook')) {
            $this->info('🗑️ Deleting Webhook...');
            $delRes = $telegramService->deleteWebhook();
            $this->line(json_encode($delRes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } elseif ($webhookUrl = $this->option('webhook')) {
            $secret = $this->option('secret') ?? config('services.telegram.webhook_secret');
            $this->info("🔗 Setting Webhook to: {$webhookUrl}");
            if ($secret) {
                $this->info("🔒 Using Secret Token: " . substr($secret, 0, 4) . '****');
            }
            $setRes = $telegramService->setupWebhook($webhookUrl, $secret);
            $this->line(json_encode($setRes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // 4. Output Webhook Status
        $hookInfo = $telegramService->getWebhookInfo();
        $this->info('📡 Current Webhook Info:');
        $this->line(json_encode($hookInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 5. Group Audit handling
        if ($this->option('audit-group')) {
            $this->info('👥 Auditing Group Administrators...');
            $admins = $telegramService->getChatAdministrators();
            if (empty($admins)) {
                $this->warn('⚠️ No administrators found or Bot is not an Admin in the configured Group.');
            } else {
                $rows = [];
                foreach ($admins as $admin) {
                    $u = $admin['user'] ?? [];
                    $rows[] = [
                        $u['id'] ?? 'N/A',
                        $u['first_name'] ?? 'N/A',
                        isset($u['username']) ? '@' . $u['username'] : 'None',
                        !empty($u['is_bot']) ? '🤖 BOT' : '👤 USER',
                        $admin['status'] ?? 'N/A',
                    ];
                }
                $this->table(['User ID', 'Name', 'Username', 'Type', 'Status'], $rows);
            }
        }

        // 6. Ban user handling
        if ($banUserId = $this->option('ban-user')) {
            $this->info("⛔ Banning User ID {$banUserId} from the group...");
            $banOk = $telegramService->banChatMember($banUserId);
            if ($banOk) {
                $this->info("✅ User {$banUserId} successfully banned and removed from the group!");
            } else {
                $this->error("❌ Failed to ban User {$banUserId}. Check if Bot has 'Ban Users' admin rights in the group.");
            }
        }

        // 7. Forensic review handling
        if ($this->option('forensics')) {
            $this->info('🛡️ Intercepted Forensics & Threat Incidents:');
            $forensicLogPath = storage_path('logs/telegram_forensics.log');
            if (file_exists($forensicLogPath)) {
                $lines = file($forensicLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $rows = [];
                foreach (array_slice($lines, -20) as $line) {
                    $entry = json_decode($line, true);
                    if ($entry) {
                        $rows[] = [
                            $entry['timestamp'] ?? 'N/A',
                            $entry['user_id'] ?? 'N/A',
                            $entry['username'] ?? 'N/A',
                            $entry['threat_type'] ?? 'N/A',
                            $entry['severity'] ?? 'N/A',
                            substr($entry['payload'] ?? '', 0, 40) . '...',
                            $entry['action'] ?? 'N/A',
                        ];
                    }
                }
                if (!empty($rows)) {
                    $this->table(['Timestamp', 'User ID', 'Username', 'Threat', 'Severity', 'Payload', 'Action'], $rows);
                } else {
                    $this->info('No incident records parsed.');
                }
            } else {
                $this->info('✅ Clean record! No security threat incidents recorded yet.');
            }
        }

        $this->info('🎉 Telegram Bot Synchronization completed successfully!');
        return self::SUCCESS;
    }
}
