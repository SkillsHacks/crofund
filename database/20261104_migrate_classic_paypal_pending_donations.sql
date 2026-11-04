-- Preserve in-flight Classic PayPal checkouts when moving their records into donations.
UPDATE donations d
INNER JOIN pending_donations pd
    ON pd.transaction_id = d.transaction_id
   AND d.payment_gateway = 'paypal_standard'
SET d.payment_token = pd.token,
    d.user_id = COALESCE(d.user_id, pd.user_id)
WHERE pd.status = 'completed'
  AND d.payment_token IS NULL;

INSERT IGNORE INTO donations (
    campaign_id,
    user_id,
    amount,
    original_amount,
    payment_gateway,
    status,
    donor_name,
    donor_email,
    transaction_id,
    gateway_response,
    payment_token,
    created_at,
    updated_at,
    donated_at,
    currency
)
SELECT
    pd.campaign_id,
    pd.user_id,
    CASE WHEN pd.status = 'completed' THEN ROUND(pd.amount_requested * 0.7, 2) ELSE pd.amount_requested END,
    CASE WHEN pd.status = 'completed' THEN pd.amount_requested ELSE NULL END,
    'paypal_standard',
    pd.status,
    COALESCE(pd.donor_name, 'Anonymous'),
    pd.donor_email,
    pd.transaction_id,
    NULL,
    pd.token,
    pd.created_at,
    pd.completed_at,
    COALESCE(pd.completed_at, pd.created_at),
    pd.currency_code
FROM pending_donations pd
WHERE pd.status IN ('pending', 'completed', 'failed');
