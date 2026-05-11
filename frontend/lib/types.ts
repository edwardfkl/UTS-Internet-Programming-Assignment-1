export type AuthUser = {
  id: number;
  name: string;
  email: string;
  avatar_url: string | null;
  is_admin: boolean;
};

export type UserProfile = {
  id: number;
  name: string;
  email: string;
  avatar_url: string | null;
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
  average_rating?: number | null;
  review_count?: number;
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

export type OrderStatus =
  | "cart"
  | "pending_payment"
  | "paid"
  | "shipped"
  | "completed"
  | "cancelled";

export type CheckoutResult = {
  order_reference: string;
  order_token: string;
  status: OrderStatus;
  payment_method: PaymentMethod;
  placed_at: string;
  promo_code: string | null;
  discount_amount: number;
  subtotal_amount: number;
  total_amount: number;
  total: number;
  lines: { name: string; quantity: number; line_total: number }[];
  shipping: OrderShipping;
};

export type UserOrderSummary = {
  id: number;
  reference: string;
  status: OrderStatus;
  payment_method: PaymentMethod | null;
  promo_code: string | null;
  discount_amount: number;
  subtotal_amount: number;
  total_amount: number;
  items_count: number;
  placed_at: string | null;
  created_at: string | null;
};

export type UserOrderDetail = UserOrderSummary & {
  lines: {
    id: number;
    quantity: number;
    unit_price: number;
    line_total: number;
    product: Pick<Product, "id" | "name" | "image_url"> & { price?: string };
  }[];
  shipping: OrderShipping;
};

export type ProductReview = {
  id: number;
  rating: number;
  comment: string | null;
  created_at: string | null;
  user: {
    id: number;
    name: string;
    avatar_url: string | null;
  } | null;
};

export type ProductReviewsResponse = {
  data: ProductReview[];
  average_rating: number | null;
  review_count: number;
};

export type PromoPreview = {
  valid: boolean;
  code: string | null;
  discount: number;
  total: number;
  message?: string;
};
