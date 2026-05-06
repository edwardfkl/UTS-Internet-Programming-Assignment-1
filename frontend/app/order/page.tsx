"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useLocale } from "@/contexts/locale-context";
import { useEffect, useState } from "react";
import { fetchUserOrders } from "@/lib/api";
import { parsePrice } from "@/lib/money";
import { useAuth } from "@/contexts/auth-context";

interface Order {
  id: number;
  placed_at: string | null;
  total_amount: number;
  status: string;
  payment_method: string | null;
  user_id: string;
}

export default function OrderPage() {
  const router = useRouter();
  const { t } = useLocale();
  const { user, ready: authReady } = useAuth();

  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      const fetchOrders = async () => {
        try {
          const response = await fetchUserOrders();
          setOrders(response.data || response);
        } catch (err: any) {
          console.error("Failed to fetch orders:", err);
          setError("Could not load orders.");
        } finally {
          setLoading(false);
        }
      };

      fetchOrders();
    }
  }, [user, router]);

  return (
    <div className="min-h-full">
      <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <h1 className="text-2xl font-bold mb-6 text-gray-900">Order History</h1>

        {loading ? (
          <p className="text-gray-500">Loading your orders...</p>
        ) : error ? (
          <p className="text-red-500">{error}</p>
        ) : orders.length === 0 ? (
          <div className="text-center py-12 bg-gray-50 rounded-lg">
            <p className="text-gray-500 mb-4">
              You haven't placed any orders yet.
            </p>
            <Link
              href="/"
              className="text-[#964B00] font-medium hover:underline"
            >
              Start shopping &rarr;
            </Link>
          </div>
        ) : (
          <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Order ID
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Date Placed
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Payment
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Amount
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {orders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">
                      #{order.id}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {order.placed_at
                        ? new Date(order.placed_at).toLocaleDateString()
                        : "N/A"}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500 capitalize">
                      {order.payment_method || "---"}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900 font-semibold">
                      {order.total_amount
                        ? parsePrice(order.total_amount)
                        : "---"}
                    </td>
                    <td className="px-6 py-4 text-sm">
                      <span
                        className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        ${
                          order.status === "paid"
                            ? "bg-green-100 text-green-800"
                            : "bg-blue-100 text-blue-800"
                        }`}
                      >
                        {order.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </main>
    </div>
  );
}
