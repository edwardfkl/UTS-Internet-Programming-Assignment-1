import type { PaymentMethod } from "./types";

export const PAYMENT_OPTIONS: {
  id: PaymentMethod;
  label: string;
  description: string;
}[] = [
  {
    id: "atm_transfer",
    label: "Bank transfer (ATM / internet banking)",
    description:
      "Pay from any Australian bank using BSB and account number. May appear as OSKO or direct credit.",
  },
  {
    id: "pay_id",
    label: "PayID",
    description:
      "Use your banking app to send to our PayID (email type). Fast transfer in many cases.",
  },
  {
    id: "bpay",
    label: "BPAY",
    description:
      "Pay from online banking using our biller code and your reference (BPAY View compatible where available).",
  },
];

/** Demo-only credentials — coursework placeholder, not real accounts. */
export function paymentDetailBlocks(
  method: PaymentMethod,
  orderReference: string,
  totalFormatted: string,
): { title: string; lines: { label: string; value: string }[] }[] {
  const refNote = `Quote reference: ${orderReference}`;
  const amountLine = { label: "Amount due", value: totalFormatted };

  switch (method) {
    case "atm_transfer":
      return [
        {
          title: "Bank transfer details (demo)",
          lines: [
            amountLine,
            { label: "Account name", value: "Studio Supply Co. Demo Pty Ltd" },
            { label: "BSB", value: "062-000" },
            { label: "Account number", value: "1234 5678" },
            { label: "Reference / narration", value: orderReference },
            {
              label: "Note",
              value:
                "Assignment demo only. Do not send real money to these details.",
            },
          ],
        },
      ];
    case "pay_id":
      return [
        {
          title: "PayID (demo)",
          lines: [
            amountLine,
            { label: "PayID type", value: "Email" },
            { label: "PayID", value: "payid.demo@studio-supply.edu" },
            { label: "Description", value: refNote },
            {
              label: "Note",
              value:
                "Assignment demo only. This PayID is not registered for real payments.",
            },
          ],
        },
      ];
    case "bpay":
      return [
        {
          title: "BPAY (demo)",
          lines: [
            amountLine,
            { label: "Biller code", value: "12345" },
            {
              label: "Customer reference",
              value: orderReference.replace(/\D/g, "").slice(-10) || "0000000001",
            },
            { label: "Note", value: refNote },
            {
              label: "Disclaimer",
              value:
                "Coursework placeholder. Use real BPAY details only from genuine invoices.",
            },
          ],
        },
      ];
    default:
      return [];
  }
}
