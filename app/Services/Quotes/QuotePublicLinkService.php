<?php

namespace App\Services\Quotes;

use App\Models\QuoteDelivery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generates and persists the customer-facing public quote URL.
 *
 * Uses an opaque random token (64 hex characters) rather than a signed URL
 * because:
 *   1. Shorter URL — important for SMS character limits.
 *   2. No expiry baked into the URL — we control longevity server-side.
 *   3. Persistent — retries always return the same URL, not a new one.
 *   4. No sequential ID exposure — tokens are not guessable.
 *
 * The public route (/q/{token}) is defined in routes/web.php (Step 7)
 * and requires no authentication.
 *
 * Idempotency: if a token is already stored on the delivery, the same URL
 * is returned without generating a new token.
 */
class QuotePublicLinkService
{
    /**
     * Generate (or retrieve) the public URL for a delivery.
     *
     * Safe to call multiple times — always returns the same URL
     * once generated for a given delivery.
     */
    public function generateForDelivery(QuoteDelivery $delivery): string
    {
        // ── Idempotency check ─────────────────────────────────────────────────
        if ($delivery->public_url && $delivery->public_token) {
            return $delivery->public_url;
        }

        // ── Generate a secure opaque token ────────────────────────────────────
        // 64 random hex characters = 256 bits of entropy.
        // Unique constraint on the column provides a database-level guard.
        $token = Str::random(64);

        $url = route('quotes.public.view', ['token' => $token]);

        $delivery->update([
            'public_token' => $token,
            'public_url'   => $url,
        ]);

        Log::info('quote_delivery.public_url_generated', [
            'delivery_id' => $delivery->id,
            'quote_id'    => $delivery->quote_id,
        ]);

        return $url;
    }

    /**
     * Find a delivery by its public token.
     * Returns null if the token does not exist.
     *
     * Used by the public quote controller to resolve the token from the URL.
     */
    public function findDeliveryByToken(string $token): ?QuoteDelivery
    {
        return QuoteDelivery::where('public_token', $token)
            ->with(['quote.items.product.category'])
            ->first();
    }
}
