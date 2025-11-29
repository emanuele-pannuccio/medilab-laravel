<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateInternalToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:internal 
                            {--abilities= : Lista di permessi separati da virgola (es. "read,write")}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = "agent@mediclinic.org";
        $tokenName = "internal";
        
        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            $this->error("Errore: Nessun utente trovato con l'email: $email");
            return 1;
        }

        $abilitiesInput = $this->option('abilities');
        $abilities = $abilitiesInput ? explode(',', $abilitiesInput) : ['*'];

        $token = $user->createToken($tokenName, $abilities);

        $this->info("Token generato con successo per: {$user->name} ({$email})");
        $this->newLine();
        
        $this->warn('COPIA QUESTO TOKEN ORA. Non sarà possibile vederlo di nuovo:');
        $this->line($token->plainTextToken);
        
        $this->newLine();
        $this->table(
            ['Nome Token', 'Abilities', 'ID Token'],
            [[
                $tokenName, 
                implode(', ', $abilities),
                $token->accessToken->id
            ]]
        );

        return 0;
    }
}
