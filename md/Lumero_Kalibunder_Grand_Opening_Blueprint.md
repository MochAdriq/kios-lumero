# REDESIGN BLUEPRINT & SYSTEM SPECIFICATION: LUMERO KALIBUNDER OUTLET GRAND OPENING EVENT
**Technical Specifications, UI/UX Architecture, Logic Flow, & Marketing Copywriting**
**Primary Target:** Online-to-Offline (O2O) Traffic Driving, Impulse Lead Generation, & Frictionless User Experience.

---

## I. STRATEGIC CONCEPTS & ARCHITECTURAL DECISIONS

1. **Zero-Loss Gamification (100% Win Rate):**
   There is no "Lose" or "Try Again" option on the Wheel of Fortune (Roulette). Every spin is mathematically guaranteed to win a prize to maximize user excitement and drive immediate foot traffic to the physical outlet.
2. **Visual Hype vs. Mathematical Reality (Zero-Stock Visual Retention):**
   High-value marquee prizes (e.g., Smartphone, Whole Chicken Package) that reach `0` stock in the Admin Panel will **remain visibly present on the frontend roulette wheel** to preserve promotional hype and attraction. However, the backend probability algorithm dynamically locks their winning chance to `0%`, making it mathematically impossible for the needle to land on them once depleted.
3. **Psychological O2O Steering (Kalibunder Outlet Focus):**
   While the backend system (`modules/branches`) technically supports reward verification and redemption across all Lumero branches nationwide, all frontend marketing copywriting, urgency countdowns, and embedded location maps are exclusively tailored to direct foot traffic to the **Kalibunder Outlet**.
4. **Frictionless-to-Verified Lead Generation:**
   Users spin the wheel completely frictionless without prior registration or login. Verification via WhatsApp OTP is requested *after* the user sees the prize they have won, leveraging the psychological principle of *loss aversion* (users are significantly more willing to verify their phone number to secure a reward they feel they already own).

---

## II. UI/UX REDESIGN STRUCTURE (`member/index.php`)

### A. Top Navigation Bar (Navbar)
* **Design Concept:** Borderless floating navbar with a clean white/transparent background, eliminating rigid dividing lines for a modern, premium aesthetic.
* **Layout Structure:**
  * **Center:** Lumero Brand Logo (Primary brand identity focus).
  * **Right:** Call-to-Action (CTA) Button labeled **"Claim My Reward"** or **"Access My Tickets"** (Replacing rigid, technical terms like "Login" or "Register").
* **Right Button Action:** Directs the user to **Flow A** (Standard Login Flow -> OTP Verification -> Regular Member Dashboard).

### B. Hero Section (The Wheel of Fortune - Roulette)
* **Visual Design:** A dominant, vibrant Roulette Wheel positioned at the top center of the hero section, surrounded by subtle festive animations (e.g., glowing sparkles or confetti) to establish a high-reward atmosphere.
* **Roulette Wheel Items (All Items Visually Retained 100% of the Time):**
  1. Smartphone (Visual Hype / Admin Stock Controlled)
  2. Whole Chicken Package (Visual Hype / Admin Stock Controlled)
  3. Chicken Package + Favorite Sauce
  4. Exclusive Lumero Tumbler
  5. Lumero Soft Serve Ice Cream
* **Hero Copywriting (Persuasive, Non-Technical & Marketing-Driven):**
  * **Headline:** *"Kalibunder Outlet Grand Opening Party! Spin the Wheel & Claim Your Free Treat Today!"*
  * **Sub-headline:** *"No complicated requirements! Just spin, win your favorite treat, and present your digital ticket to our cashier."*
  * **Wheel CTA Button:** **"SPIN & WIN NOW!"**

### C. Supporting Content Sections (Below Hero Section)
1. **Section 1: Featured Products (Appetite Trigger)**
   * **Content:** High-resolution, aesthetically pleasing photo grid of Lumero's signature menu items—specifically highlighting products featured on the wheel (e.g., Favorite Sauce Chicken, Soft Serve Ice Cream).
   * **Copywriting:** *"Warm, Crispy, and Flavorful Delights Waiting for You at Our New Outlet."*
2. **Section 2: Urgency & Kalibunder Outlet Location (O2O Driver)**
   * **Content:** Clear promotional timeframe (e.g., interactive countdown timer for the 3-day Grand Opening window) and an embedded Google Maps navigation card pointing directly to the Kalibunder branch.
   * **Copywriting:** *"Exclusively for Kalibunder Residents! Redeem Your Reward Ticket Before It Expires. Check Our New Outlet Location Here."*
3. **Section 3: Live Social Proof Feed (FOMO Trigger)**
   * **Content:** A subtle, automated ticker floating at the bottom corner of the screen displaying real-time (or simulated real-time) reward claims.
   * **Copywriting:** *"0857-xxxx-2918 just secured an Exclusive Tumbler Reward Ticket!"*

---

## III. LOGIC FLOW ARCHITECTURE & SYSTEM INTEGRATION

### Flow A: Standard Login Flow (Navbar CTA / Regular Scan)
1. User clicks **"Claim My Reward"** or **"Access My Tickets"** on the top right navbar.
2. System displays WhatsApp Phone Number input modal -> Sends OTP -> Verifies OTP.
3. User is redirected directly to `member/dashboard.php` (Standard Member Dashboard interface).

### Flow B: Event Roulette Flow (Impulse Lead Gen & Gamification)
1. User clicks **"SPIN & WIN NOW!"** -> Wheel spins -> Wheel lands on Prize X (determined by backend probability calculation).
2. **The Hook (Reward Confirmation Modal):**
   * *Copywriting:* *"WOOHOO, CONGRATULATIONS! 1 [Prize Name] is Officially Yours! Where should we send your official VIP redemption ticket?"*
   * *Input Field:* WhatsApp Phone Number.
   * *CTA Button:* **"Send Official Ticket to My WhatsApp"**
3. **OTP Verification Step (Friction Shifting):**
   * *Modal Copywriting:* *"Just one final step! Enter the 4-digit secret code we just whispered to your WhatsApp to ensure no one else claims your reward."*
4. **Post-OTP Verification Branching Logic:**
   * **Condition 1: New Phone Number (Not Registered & No Prior Event Claim)**
     * System performs *silent registration* in the `users` / `members` table.
     * Generates a unique reward claim record in the `reward_claims` table.
     * Redirects user to **`member/reward-claim.php`** (Displays celebratory animation, prize details, expiration date, and the unique **QR Code / Cashier Claim Code**).
   * **Condition 2: Existing Member (Registered BUT Has Not Claimed This Event's Reward)**
     * System generates a new reward claim record in `reward_claims` linked to the existing `user_id`.
     * Redirects user to **`member/reward-claim.php`** to view their newly won reward ticket.
   * **Condition 3: Existing Member (Already Claimed a Grand Opening Roulette Reward)**
     * System intercepts double-claiming attempts.
     * Redirects user directly to **`member/dashboard.php`**.
     * Triggers a **Welcoming Gold/Green Toast Alert** (Never display a harsh red error message that blames the user):
       * *Toast Copywriting:* *"Oops! You have already secured your Grand Opening Reward Ticket previously. We have safely stored it in your Reward Wallet below so it won't get lost. Let's visit the Kalibunder outlet to redeem it!"*

---

## IV. SYSTEM VULNERABILITY MITIGATION (HOLE PATCHING LEDGER)

| No | Vulnerability / Hole | Potential Business Impact | Engineered System Patch & Solution |
| :--- | :--- | :--- | :--- |
| 1. | **"Tuyul" Fraud (Fake Phone Number Spam)** | Malicious actors exploiting the frictionless flow using random/fake phone numbers via incognito browser tabs to drain high-value rewards. | **Mandatory WhatsApp OTP Verification** via existing `WhatsAppGateway.php` before generating any QR code. At the POS counter, cashiers visually verify the last 4 digits of the customer's WhatsApp number against the POS scanner screen. |
| 2. | **Budget Overrun (Zero-Stock Payouts)** | High-cost marquee prizes (Smartphones, Whole Chickens) exceeding marketing budget due to luck variance or high traffic spikes. | **Hard Stock Limit** implementation in the Admin Panel. When a user spins, the backend checks remaining stock. If `stock == 0`, the item's probability chance is overridden to `0%` and weight is redistributed to lower-tier items (Ice Cream/Tumbler), **while maintaining the item's visual presence on the wheel**. |
| 3. | **Expired Reward Hoarding** | Users claiming roulette rewards online but attempting to redeem them months later after the Kalibunder Grand Opening event has ended. | Strict `expired_at` timestamp embedding on every generated reward record (e.g., valid only from Day 1 to Day 5 of the event). If scanned past expiration, the POS scanner rejects the QR with: *"Grand Opening promotional ticket has expired."* |
| 4. | **Double Redemption at POS Counter** | Cashiers forgetting to complete the transaction or users attempting to re-scan a screenshot of a previously redeemed QR Code. | **Atomic Real-Time Status Update**. When scanned at `modules/pos`, the database atomically updates `reward_claims.status` from `PENDING` to `CLAIMED`, logging the `claimed_at` timestamp and `branch_id`. Any subsequent scan attempt triggers an immediate audio/visual alert on the POS: *"ERROR: Ticket Already Redeemed!"* |

---

## V. RECOMMENDED REPOSITORY FILE STRUCTURE (`kios-lumero`)

To seamlessly integrate this gamification architecture without disrupting existing core operations or standard POS workflows, the following repository modifications are specified:

```text
kios-lumero/
│
├── database/
│   └── 011_create_roulette_event_tables.sql  # (NEW) Schema for event_prizes (stock, chance, is_active) & reward_claims (user_id, prize_id, qr_code, status, expired_at)
│
├── core/
│   └── Auth.php                              # (REVISED) Add silentRegistration() method & event claim status check upon successful OTP verification
│
├── helpers/
│   ├── WhatsAppGateway.php                   # (EXISTING) Utilized for sending promotional, non-technical OTP verification messages
│   └── RouletteHelper.php                    # (NEW) Probability engine handling weighted random selection and zero-stock fallback logic
│
├── member/
│   ├── index.php                             # (TOTAL REDESIGN) Implement borderless Navbar, Wheel of Fortune Hero, Featured Menu, & Kalibunder Maps
│   ├── login.php                             # (REVISED) Adapt OTP verification modal with persuasive marketing copywriting
│   ├── reward-claim.php                      # (NEW) Dedicated landing page displaying the generated QR Code, prize specs, and O2O redemption instructions
│   └── dashboard.php                         # (REVISED) Inject "My Reward Wallet" banner & welcoming Toast Alert for users who already claimed
│
└── modules/
    ├── loyalty/
    │   └── LoyaltyController.php             # (REVISED) API endpoints handling wheel spin requests, probability resolution, and OTP validation
    └── pos/
        ├── POSController.php                 # (REVISED) Inject scanner endpoint for Grand Opening QR Code validation and atomic status updates
        └── views/
            └── redemption_modal.php          # (REVISED) POS UI displaying reward details, customer phone verification, and "Confirm Redemption" action
```

---

## VI. ROULETTE PROBABILITY BACKEND ENGINE (`RouletteHelper.php`)

```php
<?php
/**
 * RouletteHelper.php
 * Handles weighted random prize selection with dynamic zero-stock override.
 */
class RouletteHelper {

    /**
     * Executes a weighted random roll for a specific event.
     * Ensures zero-stock items cannot be won regardless of initial chance settings.
     *
     * @param int $eventId
     * @return array Selected prize data
     */
    public static function spinWheel($eventId) {
        // 1. Fetch all active prizes configured for this event
        $prizes = Database::query("SELECT * FROM event_prizes WHERE event_id = ? AND is_active = 1", [$eventId]);
        
        $validPrizes = [];
        $totalWeight = 0;

        // 2. Filter & Override: If stock <= 0, dynamically lock weight to 0
        foreach ($prizes as $prize) {
            $weight = ((int)$prize['stock'] > 0) ? (float)$prize['chance_percentage'] : 0;
            
            if ($weight > 0) {
                $validPrizes[] = [
                    'id'     => $prize['id'],
                    'name'   => $prize['name'],
                    'weight' => $weight,
                    'stock'  => $prize['stock']
                ];
                $totalWeight += $weight;
            }
        }

        // 3. Safety Fallback: If all limited stocks are exhausted, default to unlimited item (e.g., Ice Cream)
        if ($totalWeight <= 0 || empty($validPrizes)) {
            return self::getFallbackPrize($eventId);
        }

        // 4. Execute Weighted Random Selection
        $randomRoll = mt_rand(1, (int)($totalWeight * 100)) / 100;
        $currentWeight = 0;

        foreach ($validPrizes as $vp) {
            $currentWeight += $vp['weight'];
            if ($randomRoll <= $currentWeight) {
                // Decrement stock atomically in actual transaction controller
                return $vp;
            }
        }
        
        // Final fallback to ensure 100% win rate
        return self::getFallbackPrize($eventId);
    }

    /**
     * Retrieves the default guaranteed reward (unlimited promo stock).
     */
    private static function getFallbackPrize($eventId) {
        return Database::query("SELECT * FROM event_prizes WHERE event_id = ? AND is_default_fallback = 1 LIMIT 1", [$eventId])[0];
    }
}
?>
```
