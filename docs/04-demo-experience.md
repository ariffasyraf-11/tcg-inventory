# Demo-first user experience

## Demo stages
1. **Local UX prototype:** `/demo` displays fictional products, stock totals, low-stock marker and recent movements, with session-only stock receipts/adjustments/reset. No login or persistent database.
2. **Domain-backed demo:** after workspace authorization, migrations and stock actions are implemented, seed a dedicated disposable demo workspace and exercise exactly the same flow.
3. **Optional hosted demo:** gated/public read-only or disposable sessions only; never expose mutable shared inventory or production reset operations.

## Local prototype fixture
- Sample Dragon #001, Near Mint, English, Regular — single, 12 units, threshold 3
- Sample Mage #014, Lightly Played, English, Foil — single, 4 units, threshold 3
- Starter Deck A — sealed, 8 units, threshold 2
- Booster Box B — sealed, 2 units, threshold 3

## First walkthrough
From Sample Dragon's 12 units, select Receive, add 5 units at a sample unit cost and confirm 17 on hand. Use Adjust Out, remove 1 with a required reason and confirm 16 on hand. Verify the chronological +5 and -1 activity entries. Reset the session demo and confirm stock returns to 12. Test that negative stock is rejected.

## UX checklist
Responsive cards/tables, keyboard-usable controls, server-side validation errors, clear success messages, quantity and low-stock indicators, explicit **demo** label, and an obvious reset action. Demo totals are illustrative and must not claim production FIFO valuation.
