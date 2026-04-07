export type AuthUser = {
  id: number;
  name: string;
  email: string;
};

export type UserProfile = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  shipping_recipient_name: string | null;
  shipping_line1: string | null;
  shipping_line2: string | null;
  shipping_city: string | null;
  shipping_state: string | null;
  shipping_postcode: string | null;
  shipping_country: string | null;
};

export type ShippingForm = {
  recipient_name: string;
  phone: string;
  line1: string;
  line2: string;
  city: string;
  state: string;
  postcode: string;
  country: string;
};

export type OrderShipping = {
  recipient_name: string;
  phone: string;
  line1: string;
  line2: string | null;
  city: string;
  state: string;
  postcode: string;
  country: string;
};

export type Product = {
  id: number;
  name: string;
  description: string | null;
  price: string;
  image_url: string | null;
  stock: number;
};

export type CartLine = {
  id: number;
  quantity: number;
  line_total: number;
  product: Product;
};

export type CartResponse = {
  status: string;
  items: CartLine[];
  total: number;
};

export type PaymentMethod = "atm_transfer" | "pay_id" | "bpay";

export type CheckoutResult = {
  order_reference: string;
  order_token: string;
  status: string;
  payment_method: PaymentMethod;
  placed_at: string;
  total: number;
  lines: { name: string; quantity: number; line_total: number }[];
  shipping: OrderShipping;
};
