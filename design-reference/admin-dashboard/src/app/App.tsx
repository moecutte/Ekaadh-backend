import { useState } from "react";
import {
  LayoutDashboard, CalendarDays, Ticket, DollarSign, LogOut,
  Bell, Search, Plus, Check, X, Edit2, Trash2, Eye,
  Upload, ArrowUpRight, ArrowDownRight, CreditCard, Banknote,
  UserCheck, Wallet, CheckCircle2, ChevronDown, Building2,
  Percent, MapPin, FileText, TrendingUp, Download,
} from "lucide-react";
import {
  AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer,
} from "recharts";

// ─── Types ────────────────────────────────────────────────────────────────────

type Role = "organizer" | "admin";
type Screen =
  | "dashboard"
  | "create-event"
  | "my-events"
  | "earnings"
  | "org-approvals"
  | "orders-payments"
  | "commission"
  | "payouts";

// ─── Mock data ────────────────────────────────────────────────────────────────

const salesData = [
  { day: "Jun 6", revenue: 2100 },
  { day: "Jun 9", revenue: 2900 },
  { day: "Jun 12", revenue: 2250 },
  { day: "Jun 15", revenue: 4400 },
  { day: "Jun 18", revenue: 4750 },
  { day: "Jun 21", revenue: 3600 },
  { day: "Jun 24", revenue: 6000 },
  { day: "Jun 27", revenue: 4900 },
  { day: "Jun 30", revenue: 7250 },
  { day: "Jul 3", revenue: 6600 },
  { day: "Jul 5", revenue: 8400 },
];

const recentOrders = [
  { id: "ORD-2847", buyer: "Amina Hassan", event: "Mogadishu Tech Summit", tickets: 2, amount: "$120", method: "Zaad", status: "Confirmed", date: "Jul 5" },
  { id: "ORD-2846", buyer: "Omar Farah", event: "Somali Cultural Night", tickets: 4, amount: "$180", method: "eDahab", status: "Confirmed", date: "Jul 5" },
  { id: "ORD-2845", buyer: "Hodan Ali", event: "Mogadishu Tech Summit", tickets: 1, amount: "$60", method: "Zaad", status: "Pending", date: "Jul 4" },
  { id: "ORD-2844", buyer: "Ismail Warsame", event: "East Africa Business Forum", tickets: 3, amount: "$270", method: "eDahab", status: "Confirmed", date: "Jul 4" },
  { id: "ORD-2843", buyer: "Faadumo Abdi", event: "Somali Cultural Night", tickets: 2, amount: "$90", method: "Zaad", status: "Refunded", date: "Jul 3" },
];

const myEventsData = [
  { id: 1, name: "Mogadishu Tech Summit 2026", cat: "Technology", date: "Jul 15, 2026", status: "Published", sold: 234, total: 500, revenue: "$14,040", img: "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=80&h=52&fit=crop&auto=format" },
  { id: 2, name: "Somali Cultural Night", cat: "Culture", date: "Jul 22, 2026", status: "Published", sold: 88, total: 200, revenue: "$3,960", img: "https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=80&h=52&fit=crop&auto=format" },
  { id: 3, name: "East Africa Business Forum", cat: "Business", date: "Aug 3, 2026", status: "Draft", sold: 0, total: 300, revenue: "$0", img: "https://images.unsplash.com/photo-1556761175-4b46a572b786?w=80&h=52&fit=crop&auto=format" },
  { id: 4, name: "Hargeisa Music Festival", cat: "Music", date: "Aug 18, 2026", status: "Under Review", sold: 0, total: 1000, revenue: "$0", img: "https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=80&h=52&fit=crop&auto=format" },
];

const payoutHistory = [
  { id: "PAY-441", period: "Jun 1–30, 2026", gross: "$12,400", commission: "$1,240", net: "$11,160", status: "Paid", date: "Jul 1, 2026" },
  { id: "PAY-398", period: "May 1–31, 2026", gross: "$8,750", commission: "$875", net: "$7,875", status: "Paid", date: "Jun 1, 2026" },
  { id: "PAY-356", period: "Apr 1–30, 2026", gross: "$6,200", commission: "$620", net: "$5,580", status: "Paid", date: "May 1, 2026" },
];

const platformSales = [
  { month: "Feb", revenue: 48000 },
  { month: "Mar", revenue: 62000 },
  { month: "Apr", revenue: 55000 },
  { month: "May", revenue: 78000 },
  { month: "Jun", revenue: 91000 },
  { month: "Jul", revenue: 74000 },
];

const pendingOrgs = [
  { id: "ORG-089", name: "Habiba Events Ltd.", contact: "habiba@events.so", applied: "Jul 3", type: "Music, Culture" },
  { id: "ORG-090", name: "Galool Production", contact: "info@galool.so", applied: "Jul 4", type: "Technology" },
  { id: "ORG-091", name: "Somali Arts Collective", contact: "arts@collective.so", applied: "Jul 5", type: "Arts, Culture" },
];

const pendingEventsList = [
  { id: "EVT-312", name: "Bosaso Youth Summit", organizer: "Future Leaders SO", date: "Aug 10" },
  { id: "EVT-313", name: "Kismayo Trade Expo", organizer: "Jubba Commerce", date: "Aug 14" },
  { id: "EVT-314", name: "Berbera Port Conference", organizer: "Galool Production", date: "Sep 2" },
];

const allOrgsData: Array<{
  id: string; name: string; contact: string; events: number;
  revenue: string; status: string; override: number | null;
}> = [
  { id: "ORG-001", name: "Horizon Events", contact: "hello@horizon.so", events: 12, revenue: "$145,200", status: "Active", override: null },
  { id: "ORG-002", name: "Star Productions", contact: "info@starso.com", events: 8, revenue: "$98,400", status: "Active", override: 8 },
  { id: "ORG-003", name: "Mogadishu Gigs", contact: "mgig@mo.so", events: 5, revenue: "$42,000", status: "Active", override: null },
  { id: "ORG-004", name: "Future Leaders SO", contact: "fl@futureleaders.so", events: 3, revenue: "$18,750", status: "Active", override: 7 },
];

const allOrdersData = [
  { id: "ORD-2847", buyer: "Amina Hassan", event: "Mogadishu Tech Summit", organizer: "Horizon Events", amount: "$120.00", commission: "$12.00", method: "Zaad", status: "Confirmed", date: "Jul 5, 2026" },
  { id: "ORD-2846", buyer: "Omar Farah", event: "Somali Cultural Night", organizer: "Star Productions", amount: "$180.00", commission: "$18.00", method: "eDahab", status: "Confirmed", date: "Jul 5, 2026" },
  { id: "ORD-2845", buyer: "Hodan Ali", event: "Mogadishu Tech Summit", organizer: "Horizon Events", amount: "$60.00", commission: "$6.00", method: "Zaad", status: "Pending", date: "Jul 4, 2026" },
  { id: "ORD-2844", buyer: "Ismail Warsame", event: "East Africa Business Forum", organizer: "Mogadishu Gigs", amount: "$270.00", commission: "$27.00", method: "eDahab", status: "Confirmed", date: "Jul 4, 2026" },
  { id: "ORD-2843", buyer: "Faadumo Abdi", event: "Somali Cultural Night", organizer: "Star Productions", amount: "$90.00", commission: "$9.00", method: "Zaad", status: "Refunded", date: "Jul 3, 2026" },
];

const payoutRows = [
  { id: "PO-112", organizer: "Horizon Events", events: 4, gross: "$48,200", pending: "$43,380", lastPaid: "Jun 30, 2026" },
  { id: "PO-113", organizer: "Star Productions", events: 2, gross: "$19,800", pending: "$18,018", lastPaid: "Jun 30, 2026" },
  { id: "PO-114", organizer: "Mogadishu Gigs", events: 3, gross: "$14,500", pending: "$13,195", lastPaid: "May 31, 2026" },
  { id: "PO-115", organizer: "Future Leaders SO", events: 1, gross: "$6,200", pending: "$5,766", lastPaid: "Jun 30, 2026" },
];

// ─── Shared components ────────────────────────────────────────────────────────

function Badge({ status }: { status: string }) {
  const map: Record<string, string> = {
    Published: "bg-emerald-50 text-emerald-700 border-emerald-100",
    Confirmed: "bg-emerald-50 text-emerald-700 border-emerald-100",
    Active: "bg-emerald-50 text-emerald-700 border-emerald-100",
    Paid: "bg-emerald-50 text-emerald-700 border-emerald-100",
    Draft: "bg-gray-50 text-gray-500 border-gray-200",
    Pending: "bg-amber-50 text-amber-700 border-amber-100",
    "Under Review": "bg-violet-50 text-violet-700 border-violet-100",
    Refunded: "bg-red-50 text-red-600 border-red-100",
    Rejected: "bg-red-50 text-red-600 border-red-100",
  };
  return (
    <span className={`inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${map[status] ?? "bg-gray-50 text-gray-500 border-gray-200"}`}>
      {status}
    </span>
  );
}

function StatCard({
  icon: Icon, label, value, change, changeType = "neutral", color,
}: {
  icon: any; label: string; value: string; change?: string;
  changeType?: "up" | "down" | "neutral"; color: string;
}) {
  return (
    <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <div className={`inline-flex p-2.5 rounded-lg mb-3 ${color}`}>
        <Icon size={18} className="text-white" />
      </div>
      <p className="text-2xl font-bold text-gray-900 mb-0.5 tracking-tight">{value}</p>
      <p className="text-xs text-gray-500 mb-1.5">{label}</p>
      {change && (
        <p className={`text-xs font-medium flex items-center gap-0.5 ${changeType === "up" ? "text-emerald-600" : changeType === "down" ? "text-red-500" : "text-gray-400"}`}>
          {changeType === "up" && <ArrowUpRight size={11} />}
          {changeType === "down" && <ArrowDownRight size={11} />}
          {change}
        </p>
      )}
    </div>
  );
}

function THead({ cols }: { cols: string[] }) {
  return (
    <thead>
      <tr className="bg-gray-50/70 border-b border-gray-100">
        {cols.map((c) => (
          <th key={c} className="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">
            {c}
          </th>
        ))}
      </tr>
    </thead>
  );
}

function Input({
  value, onChange, placeholder, type = "text", className = "",
}: {
  value?: string; onChange?: (v: string) => void;
  placeholder?: string; type?: string; className?: string;
}) {
  return (
    <input
      type={type}
      value={value}
      onChange={onChange ? (e) => onChange(e.target.value) : undefined}
      placeholder={placeholder}
      className={`w-full px-3.5 py-2.5 rounded-lg border border-gray-200 bg-gray-50/50 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all ${className}`}
    />
  );
}

// ─── Login ────────────────────────────────────────────────────────────────────

function LoginScreen({ onLogin }: { onLogin: (r: Role) => void }) {
  const [email, setEmail] = useState("demo@ekaadh.so");
  const [password, setPassword] = useState("demo1234");

  return (
    <div className="min-h-screen bg-[#f4f6f8] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        {/* Logo mark */}
        <div className="flex items-center justify-center gap-3 mb-8">
          <div className="w-11 h-11 bg-[#323891] rounded-xl flex items-center justify-center shadow-lg shadow-[#323891]/30">
            <Ticket size={22} className="text-white" />
          </div>
          <span className="text-[26px] font-extrabold text-gray-900 tracking-tight">Ekaadh</span>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
          <h1 className="text-xl font-bold text-gray-900 mb-1">Sign in</h1>
          <p className="text-sm text-gray-400 mb-6">Internal platform — authorized users only</p>

          <div className="space-y-4 mb-6">
            <div>
              <label className="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
              <Input value={email} onChange={setEmail} placeholder="you@ekaadh.so" />
            </div>
            <div>
              <label className="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password</label>
              <Input type="password" value={password} onChange={setPassword} />
            </div>
          </div>

          <div className="space-y-2.5">
            <p className="text-center text-xs text-gray-400">Select a demo role to continue</p>
            <button
              onClick={() => onLogin("organizer")}
              className="w-full py-2.5 px-4 bg-[#323891] hover:bg-[#0255cc] active:bg-[#0248b3] text-white text-sm font-bold rounded-xl transition-colors shadow-sm shadow-[#323891]/20"
            >
              Continue as Organizer
            </button>
            <button
              onClick={() => onLogin("admin")}
              className="w-full py-2.5 px-4 bg-gray-900 hover:bg-gray-800 text-white text-sm font-bold rounded-xl transition-colors"
            >
              Continue as Platform Admin
            </button>
          </div>
        </div>

        <p className="text-center text-xs text-gray-400 mt-5">
          Ekaadh Internal Dashboard &mdash; not for public access
        </p>
      </div>
    </div>
  );
}

// ─── Sidebar ──────────────────────────────────────────────────────────────────

const orgNav: Array<{ id: Screen; label: string; icon: any }> = [
  { id: "dashboard", label: "Dashboard", icon: LayoutDashboard },
  { id: "create-event", label: "Create Event", icon: Plus },
  { id: "my-events", label: "My Events", icon: CalendarDays },
  { id: "earnings", label: "Earnings & Payouts", icon: DollarSign },
];

const adminNav: Array<{ id: Screen; label: string; icon: any }> = [
  { id: "dashboard", label: "Dashboard", icon: LayoutDashboard },
  { id: "org-approvals", label: "Organizer Approvals", icon: UserCheck },
  { id: "orders-payments", label: "Orders & Payments", icon: CreditCard },
  { id: "commission", label: "Commission Settings", icon: Percent },
  { id: "payouts", label: "Payout Management", icon: Wallet },
];

function Sidebar({
  role, screen, setScreen, onLogout,
}: {
  role: Role; screen: Screen; setScreen: (s: Screen) => void; onLogout: () => void;
}) {
  const nav = role === "organizer" ? orgNav : adminNav;
  const user = role === "organizer"
    ? { name: "Khadar Isse", initials: "KI", role: "Event Organizer" }
    : { name: "Nasro Ahmed", initials: "NA", role: "Platform Admin" };

  return (
    <aside className="w-[232px] shrink-0 bg-white border-r border-gray-100 flex flex-col h-full">
      <div className="h-16 flex items-center px-5 border-b border-gray-100 shrink-0">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 bg-[#323891] rounded-lg flex items-center justify-center shadow-sm shadow-[#323891]/25">
            <Ticket size={15} className="text-white" />
          </div>
          <span className="text-lg font-extrabold text-gray-900 tracking-tight">Ekaadh</span>
          {role === "admin" && (
            <span className="text-[9px] font-black bg-gray-800 text-white px-1.5 py-0.5 rounded tracking-widest">ADMIN</span>
          )}
        </div>
      </div>

      <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        {nav.map(({ id, label, icon: Icon }) => {
          const active = screen === id;
          return (
            <button
              key={id}
              onClick={() => setScreen(id)}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left ${
                active
                  ? "bg-[#323891] text-white shadow-sm shadow-[#323891]/20"
                  : "text-gray-500 hover:bg-gray-50 hover:text-gray-800"
              }`}
            >
              <Icon size={16} className="shrink-0" />
              <span className="truncate leading-none">{label}</span>
            </button>
          );
        })}
      </nav>

      <div className="border-t border-gray-100 p-3 shrink-0">
        <div className="flex items-center gap-2.5 px-2 py-2 rounded-xl hover:bg-gray-50 transition-colors">
          <div className="w-8 h-8 bg-[#323891]/12 rounded-full flex items-center justify-center text-[#323891] text-xs font-black shrink-0">
            {user.initials}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-[13px] font-bold text-gray-800 truncate">{user.name}</p>
            <p className="text-[11px] text-gray-400 truncate">{user.role}</p>
          </div>
          <button
            onClick={onLogout}
            title="Log out"
            className="text-gray-300 hover:text-gray-600 transition-colors shrink-0"
          >
            <LogOut size={14} />
          </button>
        </div>
      </div>
    </aside>
  );
}

// ─── TopBar ───────────────────────────────────────────────────────────────────

const pageTitles: Record<Screen, string> = {
  "dashboard": "Dashboard",
  "create-event": "Create Event",
  "my-events": "My Events",
  "earnings": "Earnings & Payouts",
  "org-approvals": "Organizer Approvals",
  "orders-payments": "Orders & Payments",
  "commission": "Commission Settings",
  "payouts": "Payout Management",
};

function TopBar({ screen, role }: { screen: Screen; role: Role }) {
  const initials = role === "organizer" ? "KI" : "NA";
  return (
    <header className="h-16 bg-white border-b border-gray-100 flex items-center px-6 gap-4 shrink-0">
      <div className="flex-1 min-w-0">
        <h1 className="text-[15px] font-bold text-gray-900 truncate">{pageTitles[screen]}</h1>
        {screen === "dashboard" && (
          <p className="text-[11px] text-gray-400 leading-none mt-0.5">Saturday, July 5, 2026</p>
        )}
      </div>
      <div className="relative">
        <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
        <input
          className="pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl w-52 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all placeholder:text-gray-400"
          placeholder="Search..."
        />
      </div>
      <button className="relative p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
        <Bell size={17} />
        <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-[#323891] rounded-full border-2 border-white" />
      </button>
      <div className="w-8 h-8 bg-[#323891]/12 rounded-full flex items-center justify-center text-[#323891] text-[11px] font-black">
        {initials}
      </div>
    </header>
  );
}

// ─── Organizer › Dashboard ────────────────────────────────────────────────────

function OrgDashboard() {
  return (
    <div className="space-y-5">
      <div className="grid grid-cols-4 gap-4">
        <StatCard icon={CalendarDays} label="Total Events" value="4" change="+1 this month" changeType="up" color="bg-[#323891]" />
        <StatCard icon={Ticket} label="Tickets Sold" value="322" change="+48 this week" changeType="up" color="bg-sky-500" />
        <StatCard icon={DollarSign} label="Total Revenue" value="$18,000" change="+$3,200 this week" changeType="up" color="bg-violet-500" />
        <StatCard icon={Banknote} label="Pending Payout" value="$6,840" change="Available Jul 30" changeType="neutral" color="bg-amber-500" />
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div className="flex items-center justify-between mb-5">
          <div>
            <h3 className="text-sm font-bold text-gray-900">Revenue — Last 30 Days</h3>
            <p className="text-xs text-gray-400 mt-0.5">Jun 6 – Jul 5, 2026</p>
          </div>
          <div className="flex items-center gap-1.5 text-xs text-gray-400">
            <span className="w-3 h-0.5 bg-[#323891] inline-block rounded-full" />
            Revenue
          </div>
        </div>
        <ResponsiveContainer width="100%" height={210}>
          <AreaChart data={salesData} margin={{ top: 5, right: 5, left: -20, bottom: 0 }}>
            <defs>
              <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#323891" stopOpacity={0.14} />
                <stop offset="95%" stopColor="#323891" stopOpacity={0} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" vertical={false} />
            <XAxis dataKey="day" tick={{ fontSize: 11, fill: "#9ca3af" }} tickLine={false} axisLine={false} />
            <YAxis tick={{ fontSize: 11, fill: "#9ca3af" }} tickLine={false} axisLine={false} tickFormatter={(v) => `$${v / 1000}k`} />
            <Tooltip
              contentStyle={{ borderRadius: 10, border: "1px solid #e5e7eb", fontSize: 12, boxShadow: "0 4px 12px rgba(0,0,0,0.06)" }}
              formatter={(v: number) => [`$${v.toLocaleString()}`, "Revenue"]}
            />
            <Area type="monotone" dataKey="revenue" stroke="#323891" strokeWidth={2.5} fill="url(#revGrad)" dot={false} />
          </AreaChart>
        </ResponsiveContainer>
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
          <h3 className="text-sm font-bold text-gray-900">Recent Orders</h3>
          <button className="text-xs font-semibold text-[#323891] hover:text-[#0248b3] transition-colors">View all →</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <THead cols={["Order ID", "Buyer", "Event", "Qty", "Amount", "Method", "Status", "Date"]} />
            <tbody>
              {recentOrders.map((o, i) => (
                <tr key={o.id} className={`${i < recentOrders.length - 1 ? "border-b border-gray-50" : ""} hover:bg-gray-50/50 transition-colors`}>
                  <td className="px-4 py-3.5 text-[11px] font-mono text-gray-400">{o.id}</td>
                  <td className="px-4 py-3.5 text-sm font-semibold text-gray-900">{o.buyer}</td>
                  <td className="px-4 py-3.5 text-sm text-gray-500 max-w-[140px] truncate">{o.event}</td>
                  <td className="px-4 py-3.5 text-sm text-gray-600">{o.tickets}</td>
                  <td className="px-4 py-3.5 text-sm font-bold text-gray-900">{o.amount}</td>
                  <td className="px-4 py-3.5">
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded-md ${o.method === "Zaad" ? "bg-sky-50 text-sky-700" : "bg-orange-50 text-orange-700"}`}>
                      {o.method}
                    </span>
                  </td>
                  <td className="px-4 py-3.5"><Badge status={o.status} /></td>
                  <td className="px-4 py-3.5 text-xs text-gray-400">{o.date}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

// ─── Organizer › Create Event ─────────────────────────────────────────────────

interface TicketType {
  id: number; name: string; price: string; quantity: string; maxPerOrder: string;
}

function CreateEvent() {
  const [ticketTypes, setTicketTypes] = useState<TicketType[]>([
    { id: 1, name: "General Admission", price: "50", quantity: "400", maxPerOrder: "5" },
    { id: 2, name: "VIP", price: "150", quantity: "100", maxPerOrder: "2" },
  ]);

  const addTicket = () => setTicketTypes((p) => [...p, { id: Date.now(), name: "", price: "", quantity: "", maxPerOrder: "5" }]);
  const removeTicket = (id: number) => setTicketTypes((p) => p.filter((t) => t.id !== id));
  const updateTicket = (id: number, field: keyof TicketType, val: string) =>
    setTicketTypes((p) => p.map((t) => (t.id === id ? { ...t, [field]: val } : t)));

  return (
    <div className="max-w-2xl mx-auto space-y-5 pb-6">
      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <SectionHead icon={FileText} label="Event Details" />
        <div className="space-y-4">
          <div>
            <FieldLabel>Event Title *</FieldLabel>
            <Input defaultValue="Mogadishu Innovation Summit 2026" />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <FieldLabel>Category *</FieldLabel>
              <SelectField options={["Technology", "Business", "Music", "Culture", "Sports", "Education"]} />
            </div>
            <div>
              <FieldLabel>Status</FieldLabel>
              <SelectField options={["Draft", "Published"]} />
            </div>
          </div>
          <div>
            <FieldLabel>Description *</FieldLabel>
            <textarea
              rows={3}
              defaultValue="Join us for the largest technology summit in East Africa — keynote speakers, workshops, and premium networking."
              className="w-full px-3.5 py-2.5 rounded-lg border border-gray-200 bg-gray-50/50 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all resize-none"
            />
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <SectionHead icon={MapPin} label="Date, Time & Venue" />
        <div className="grid grid-cols-2 gap-4">
          <div>
            <FieldLabel>Start *</FieldLabel>
            <input
              type="datetime-local"
              defaultValue="2026-08-15T09:00"
              className="w-full px-3.5 py-2.5 rounded-lg border border-gray-200 bg-gray-50/50 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
            />
          </div>
          <div>
            <FieldLabel>End *</FieldLabel>
            <input
              type="datetime-local"
              defaultValue="2026-08-15T18:00"
              className="w-full px-3.5 py-2.5 rounded-lg border border-gray-200 bg-gray-50/50 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
            />
          </div>
          <div>
            <FieldLabel>Venue Name *</FieldLabel>
            <Input defaultValue="Mogadishu Convention Centre" />
          </div>
          <div>
            <FieldLabel>Address</FieldLabel>
            <Input defaultValue="Airport Road, Mogadishu" />
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <SectionHead icon={Upload} label="Cover Image" />
        <div className="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:border-[#323891]/40 hover:bg-[#323891]/2 transition-all cursor-pointer group">
          <div className="w-10 h-10 bg-gray-100 group-hover:bg-[#323891]/10 rounded-xl flex items-center justify-center mx-auto mb-3 transition-colors">
            <Upload size={18} className="text-gray-400 group-hover:text-[#323891] transition-colors" />
          </div>
          <p className="text-sm font-semibold text-gray-700">Drag & drop your cover image</p>
          <p className="text-xs text-gray-400 mt-1">PNG, JPG, WEBP · max 5 MB · 1200 × 630px recommended</p>
          <span className="mt-3 inline-block text-xs font-bold text-[#323891]">Browse files</span>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div className="flex items-center justify-between mb-5">
          <SectionHead icon={Ticket} label="Ticket Types" noMargin />
          <button
            onClick={addTicket}
            className="flex items-center gap-1.5 text-xs font-bold text-[#323891] hover:text-[#0248b3] transition-colors"
          >
            <Plus size={14} />
            Add row
          </button>
        </div>
        <div className="space-y-2.5">
          <div className="grid grid-cols-12 gap-3 px-1">
            {["Ticket Name", "Price ($)", "Qty", "Max/Order", ""].map((h, i) => (
              <span
                key={i}
                className={`text-[10px] font-bold text-gray-400 uppercase tracking-wider ${i === 0 ? "col-span-5" : i < 4 ? "col-span-2" : "col-span-1"}`}
              >
                {h}
              </span>
            ))}
          </div>
          {ticketTypes.map((t) => (
            <div key={t.id} className="grid grid-cols-12 gap-3 items-center bg-gray-50/60 rounded-xl p-3 border border-gray-100">
              <div className="col-span-5">
                <input
                  value={t.name}
                  onChange={(e) => updateTicket(t.id, "name", e.target.value)}
                  placeholder="e.g. General Admission"
                  className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
                />
              </div>
              <div className="col-span-2">
                <input
                  value={t.price}
                  onChange={(e) => updateTicket(t.id, "price", e.target.value)}
                  placeholder="0"
                  className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
                />
              </div>
              <div className="col-span-2">
                <input
                  value={t.quantity}
                  onChange={(e) => updateTicket(t.id, "quantity", e.target.value)}
                  placeholder="100"
                  className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
                />
              </div>
              <div className="col-span-2">
                <input
                  value={t.maxPerOrder}
                  onChange={(e) => updateTicket(t.id, "maxPerOrder", e.target.value)}
                  placeholder="5"
                  className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
                />
              </div>
              <div className="col-span-1 flex justify-end">
                <button
                  onClick={() => removeTicket(t.id)}
                  className="p-1.5 text-gray-300 hover:text-red-400 hover:bg-red-50 rounded-lg transition-all"
                >
                  <X size={14} />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="flex items-center justify-end gap-3">
        <button className="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
          Save Draft
        </button>
        <button className="px-5 py-2.5 text-sm font-bold text-white bg-[#323891] hover:bg-[#0255cc] rounded-xl transition-colors shadow-sm shadow-[#323891]/20">
          Publish Event
        </button>
      </div>
    </div>
  );
}

// ─── Organizer › My Events ────────────────────────────────────────────────────

function MyEvents() {
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
            <input className="pl-9 pr-4 py-2 text-sm bg-white border border-gray-200 rounded-xl w-48 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all placeholder:text-gray-400" placeholder="Search events..." />
          </div>
          <SelectSmall options={["All statuses", "Published", "Draft", "Under Review"]} />
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-[#323891] text-white text-sm font-bold rounded-xl hover:bg-[#0255cc] transition-colors shadow-sm shadow-[#323891]/20">
          <Plus size={14} />
          New Event
        </button>
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table className="w-full">
          <THead cols={["Event", "Date", "Status", "Tickets Sold", "Revenue", "Actions"]} />
          <tbody>
            {myEventsData.map((ev, i) => (
              <tr key={ev.id} className={`${i < myEventsData.length - 1 ? "border-b border-gray-50" : ""} hover:bg-gray-50/50 transition-colors`}>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-3">
                    <div className="w-16 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                      <img src={ev.img} alt={ev.name} className="w-full h-full object-cover" />
                    </div>
                    <div className="min-w-0">
                      <p className="text-sm font-bold text-gray-900 truncate max-w-[180px]">{ev.name}</p>
                      <p className="text-xs text-gray-400 mt-0.5">{ev.cat}</p>
                    </div>
                  </div>
                </td>
                <td className="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">{ev.date}</td>
                <td className="px-4 py-4"><Badge status={ev.status} /></td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-2">
                    <div className="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        className="h-full bg-[#323891] rounded-full"
                        style={{ width: `${Math.round((ev.sold / ev.total) * 100)}%` }}
                      />
                    </div>
                    <span className="text-xs text-gray-600 whitespace-nowrap">{ev.sold}/{ev.total}</span>
                  </div>
                </td>
                <td className="px-4 py-4 text-sm font-bold text-gray-900">{ev.revenue}</td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-0.5">
                    <IconBtn icon={Eye} />
                    <IconBtn icon={Edit2} hoverColor="text-[#323891] hover:bg-[#323891]/5" />
                    <IconBtn icon={Trash2} hoverColor="text-red-400 hover:bg-red-50" />
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Organizer › Earnings ─────────────────────────────────────────────────────

function Earnings() {
  return (
    <div className="space-y-5">
      <div className="grid grid-cols-4 gap-4">
        <StatCard icon={TrendingUp} label="Gross Sales" value="$27,150" change="All time" changeType="neutral" color="bg-[#323891]" />
        <StatCard icon={Percent} label="Commission Deducted" value="$2,715" change="10% platform fee" changeType="neutral" color="bg-gray-500" />
        <StatCard icon={DollarSign} label="Net Earnings" value="$24,435" change="After deductions" changeType="neutral" color="bg-sky-500" />
        <StatCard icon={Wallet} label="Available for Payout" value="$6,840" change="Request before Jul 30" changeType="up" color="bg-amber-500" />
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
          <h3 className="text-sm font-bold text-gray-900">Payout History</h3>
          <button className="flex items-center gap-1.5 text-xs font-semibold text-gray-400 hover:text-gray-600 transition-colors">
            <Download size={13} />
            Export CSV
          </button>
        </div>
        <table className="w-full">
          <THead cols={["Payout ID", "Period", "Gross Sales", "Commission", "Net Earned", "Status", "Paid On"]} />
          <tbody>
            {payoutHistory.map((p, i) => (
              <tr key={p.id} className={`${i < payoutHistory.length - 1 ? "border-b border-gray-50" : ""} hover:bg-gray-50/50 transition-colors`}>
                <td className="px-4 py-3.5 text-[11px] font-mono text-gray-400">{p.id}</td>
                <td className="px-4 py-3.5 text-sm text-gray-700">{p.period}</td>
                <td className="px-4 py-3.5 text-sm font-bold text-gray-900">{p.gross}</td>
                <td className="px-4 py-3.5 text-sm font-semibold text-red-400">-{p.commission}</td>
                <td className="px-4 py-3.5 text-sm font-bold text-[#323891]">{p.net}</td>
                <td className="px-4 py-3.5"><Badge status={p.status} /></td>
                <td className="px-4 py-3.5 text-sm text-gray-500">{p.date}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="px-5 py-4 border-t border-gray-50 bg-gray-50/40 flex items-center justify-between">
          <p className="text-xs text-gray-400">Payouts processed 1st of each month · Minimum $100 balance</p>
          <button className="px-4 py-2 bg-[#323891] text-white text-xs font-bold rounded-xl hover:bg-[#0255cc] transition-colors shadow-sm shadow-[#323891]/20">
            Request Payout
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── Admin › Dashboard ────────────────────────────────────────────────────────

function AdminDashboard() {
  const [approvedOrgs, setApprovedOrgs] = useState<string[]>([]);
  const [rejectedOrgs, setRejectedOrgs] = useState<string[]>([]);
  const [approvedEvts, setApprovedEvts] = useState<string[]>([]);
  const [rejectedEvts, setRejectedEvts] = useState<string[]>([]);

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-4 gap-4">
        <StatCard icon={Building2} label="Total Organizers" value="47" change="+3 this week" changeType="up" color="bg-[#323891]" />
        <StatCard icon={CalendarDays} label="Total Events" value="138" change="+12 this month" changeType="up" color="bg-sky-500" />
        <StatCard icon={Ticket} label="Tickets Sold" value="14,820" change="+1,240 this week" changeType="up" color="bg-violet-500" />
        <StatCard icon={DollarSign} label="Platform Revenue" value="$91,400" change="+$8,200 this month" changeType="up" color="bg-amber-500" />
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div className="mb-5">
          <h3 className="text-sm font-bold text-gray-900">Platform Revenue — 2026</h3>
          <p className="text-xs text-gray-400 mt-0.5">Feb – Jul 2026</p>
        </div>
        <ResponsiveContainer width="100%" height={200}>
          <BarChart data={platformSales} margin={{ top: 5, right: 5, left: -20, bottom: 0 }} barSize={40}>
            <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" vertical={false} />
            <XAxis dataKey="month" tick={{ fontSize: 11, fill: "#9ca3af" }} tickLine={false} axisLine={false} />
            <YAxis tick={{ fontSize: 11, fill: "#9ca3af" }} tickLine={false} axisLine={false} tickFormatter={(v) => `$${v / 1000}k`} />
            <Tooltip
              contentStyle={{ borderRadius: 10, border: "1px solid #e5e7eb", fontSize: 12 }}
              formatter={(v: number) => [`$${v.toLocaleString()}`, "Revenue"]}
            />
            <Bar dataKey="revenue" fill="#323891" radius={[5, 5, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </div>

      <div className="grid grid-cols-2 gap-5">
        <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h3 className="text-sm font-bold text-gray-900">Pending Organizer Approvals</h3>
            <span className="text-[11px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-100">
              {pendingOrgs.filter((o) => !approvedOrgs.includes(o.id) && !rejectedOrgs.includes(o.id)).length} pending
            </span>
          </div>
          <div className="divide-y divide-gray-50">
            {pendingOrgs.map((org) => {
              const isApproved = approvedOrgs.includes(org.id);
              const isRejected = rejectedOrgs.includes(org.id);
              return (
                <div key={org.id} className="px-5 py-3.5 flex items-center gap-3">
                  <div className="w-8 h-8 bg-[#323891]/10 rounded-full flex items-center justify-center text-[#323891] text-xs font-black shrink-0">
                    {org.name[0]}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 truncate">{org.name}</p>
                    <p className="text-xs text-gray-400">{org.contact} · {org.applied}</p>
                  </div>
                  {isApproved ? (
                    <span className="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Approved</span>
                  ) : isRejected ? (
                    <span className="text-[11px] font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full">Rejected</span>
                  ) : (
                    <div className="flex items-center gap-1.5 shrink-0">
                      <button
                        onClick={() => setApprovedOrgs((p) => [...p, org.id])}
                        className="p-1.5 bg-[#323891] text-white rounded-lg hover:bg-[#0255cc] transition-colors"
                        title="Approve"
                      >
                        <Check size={12} />
                      </button>
                      <button
                        onClick={() => setRejectedOrgs((p) => [...p, org.id])}
                        className="p-1.5 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors"
                        title="Reject"
                      >
                        <X size={12} />
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h3 className="text-sm font-bold text-gray-900">Pending Event Reviews</h3>
            <span className="text-[11px] font-bold bg-violet-50 text-violet-700 px-2 py-0.5 rounded-full border border-violet-100">
              {pendingEventsList.filter((e) => !approvedEvts.includes(e.id) && !rejectedEvts.includes(e.id)).length} pending
            </span>
          </div>
          <div className="divide-y divide-gray-50">
            {pendingEventsList.map((ev) => {
              const isApproved = approvedEvts.includes(ev.id);
              const isRejected = rejectedEvts.includes(ev.id);
              return (
                <div key={ev.id} className="px-5 py-3.5 flex items-center gap-3">
                  <div className="w-8 h-8 bg-violet-50 rounded-full flex items-center justify-center text-violet-500 shrink-0">
                    <CalendarDays size={14} />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 truncate">{ev.name}</p>
                    <p className="text-xs text-gray-400">{ev.organizer} · {ev.date}</p>
                  </div>
                  {isApproved ? (
                    <span className="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Approved</span>
                  ) : isRejected ? (
                    <span className="text-[11px] font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full">Rejected</span>
                  ) : (
                    <div className="flex items-center gap-1.5 shrink-0">
                      <button
                        onClick={() => setApprovedEvts((p) => [...p, ev.id])}
                        className="p-1.5 bg-[#323891] text-white rounded-lg hover:bg-[#0255cc] transition-colors"
                        title="Approve"
                      >
                        <Check size={12} />
                      </button>
                      <button
                        onClick={() => setRejectedEvts((p) => [...p, ev.id])}
                        className="p-1.5 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors"
                        title="Reject"
                      >
                        <X size={12} />
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

// ─── Admin › Organizer Approvals ──────────────────────────────────────────────

function OrgApprovals() {
  const [modal, setModal] = useState<typeof pendingOrgs[0] | null>(null);
  const [rejectReason, setRejectReason] = useState("");
  const [statuses, setStatuses] = useState<Record<string, "pending" | "approved" | "rejected">>({});

  const handleApprove = (id: string) => { setStatuses((p) => ({ ...p, [id]: "approved" })); setModal(null); };
  const handleReject = (id: string) => { setStatuses((p) => ({ ...p, [id]: "rejected" })); setRejectReason(""); setModal(null); };

  const rows = [
    ...allOrgsData.map((o) => ({ ...o, applied: "—", type: "Technology, Business", isPending: false })),
    ...pendingOrgs.map((o) => ({ ...o, events: 0, revenue: "$0", status: "Pending", override: null, isPending: true })),
  ];

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <SearchBox placeholder="Search organizers..." />
        <SelectSmall options={["All statuses", "Active", "Pending", "Rejected"]} />
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table className="w-full">
          <THead cols={["ID", "Name", "Contact", "Events", "Revenue", "Status", "Applied", "Action"]} />
          <tbody>
            {rows.map((org, i) => {
              const status = statuses[org.id] ?? (org.isPending ? "pending" : "approved");
              const displayStatus = status === "approved" ? "Active" : status === "rejected" ? "Rejected" : "Pending";
              return (
                <tr key={org.id} className={`${i < rows.length - 1 ? "border-b border-gray-50" : ""} hover:bg-gray-50/50 transition-colors`}>
                  <td className="px-4 py-3.5 text-[11px] font-mono text-gray-400">{org.id}</td>
                  <td className="px-4 py-3.5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-7 h-7 bg-[#323891]/10 rounded-full flex items-center justify-center text-[#323891] text-[11px] font-black shrink-0">
                        {org.name[0]}
                      </div>
                      <span className="text-sm font-semibold text-gray-900">{org.name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3.5 text-sm text-gray-500">{org.contact}</td>
                  <td className="px-4 py-3.5 text-sm text-gray-600">{org.events}</td>
                  <td className="px-4 py-3.5 text-sm font-bold text-gray-900">{org.revenue}</td>
                  <td className="px-4 py-3.5"><Badge status={displayStatus} /></td>
                  <td className="px-4 py-3.5 text-sm text-gray-400">{org.applied}</td>
                  <td className="px-4 py-3.5">
                    {org.isPending && status === "pending" ? (
                      <button
                        onClick={() => setModal(pendingOrgs.find((p) => p.id === org.id) ?? null)}
                        className="flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
                      >
                        <Eye size={12} />
                        Review
                      </button>
                    ) : (
                      <button className="p-1.5 text-gray-300 hover:text-gray-500 rounded-lg transition-colors"><Eye size={14} /></button>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {modal && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-[2px] flex items-center justify-center z-50 p-4" onClick={() => setModal(null)}>
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
              <h3 className="font-bold text-gray-900">Organizer Application</h3>
              <button onClick={() => setModal(null)} className="p-1.5 text-gray-400 hover:bg-gray-50 rounded-xl transition-all"><X size={15} /></button>
            </div>
            <div className="px-6 py-5 space-y-4">
              <div className="flex items-center gap-3 p-3.5 bg-gray-50 rounded-xl">
                <div className="w-10 h-10 bg-[#323891]/12 rounded-full flex items-center justify-center text-[#323891] font-black text-sm">{modal.name[0]}</div>
                <div>
                  <p className="font-bold text-gray-900">{modal.name}</p>
                  <p className="text-xs text-gray-400">{modal.contact}</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2.5">
                <InfoBox label="Application ID" value={modal.id} mono />
                <InfoBox label="Applied On" value={modal.applied} />
                <div className="col-span-2"><InfoBox label="Event Categories" value={modal.type} /></div>
              </div>
              <div>
                <label className="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Rejection reason (optional)</label>
                <textarea
                  value={rejectReason}
                  onChange={(e) => setRejectReason(e.target.value)}
                  rows={2}
                  placeholder="Enter reason for rejection..."
                  className="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all resize-none"
                />
              </div>
            </div>
            <div className="flex items-center gap-3 px-6 py-4 border-t border-gray-100">
              <button onClick={() => handleReject(modal.id)} className="flex-1 py-2.5 text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                Reject
              </button>
              <button onClick={() => handleApprove(modal.id)} className="flex-1 py-2.5 text-sm font-bold text-white bg-[#323891] hover:bg-[#0255cc] rounded-xl transition-colors">
                Approve
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Admin › Orders & Payments ────────────────────────────────────────────────

function OrdersPayments() {
  const [selected, setSelected] = useState<typeof allOrdersData[0] | null>(null);

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <SearchBox placeholder="Search orders..." />
        <SelectSmall options={["All methods", "Zaad", "eDahab"]} />
        <SelectSmall options={["All statuses", "Confirmed", "Pending", "Refunded"]} />
        <button className="ml-auto flex items-center gap-1.5 text-xs font-bold text-gray-500 bg-white border border-gray-200 px-3 py-2 rounded-xl hover:bg-gray-50 transition-colors">
          <Download size={13} />
          Export
        </button>
      </div>

      <div className={`grid gap-4 ${selected ? "grid-cols-[1fr_320px]" : "grid-cols-1"}`}>
        <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <THead cols={["Order", "Buyer", "Event", "Organizer", "Amount", "Method", "Status", "Date"]} />
              <tbody>
                {allOrdersData.map((o, i) => (
                  <tr
                    key={o.id}
                    onClick={() => setSelected(selected?.id === o.id ? null : o)}
                    className={`cursor-pointer transition-colors ${i < allOrdersData.length - 1 ? "border-b border-gray-50" : ""} ${selected?.id === o.id ? "bg-[#323891]/5" : "hover:bg-gray-50/50"}`}
                  >
                    <td className="px-4 py-3.5 text-[11px] font-mono text-gray-400">{o.id}</td>
                    <td className="px-4 py-3.5 text-sm font-semibold text-gray-900 whitespace-nowrap">{o.buyer}</td>
                    <td className="px-4 py-3.5 text-sm text-gray-500 max-w-[120px] truncate">{o.event}</td>
                    <td className="px-4 py-3.5 text-sm text-gray-500 max-w-[120px] truncate">{o.organizer}</td>
                    <td className="px-4 py-3.5 text-sm font-bold text-gray-900">{o.amount}</td>
                    <td className="px-4 py-3.5">
                      <span className={`text-[11px] font-bold px-2 py-0.5 rounded-md ${o.method === "Zaad" ? "bg-sky-50 text-sky-700" : "bg-orange-50 text-orange-700"}`}>
                        {o.method}
                      </span>
                    </td>
                    <td className="px-4 py-3.5"><Badge status={o.status} /></td>
                    <td className="px-4 py-3.5 text-xs text-gray-400 whitespace-nowrap">{o.date}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {selected && (
          <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5 h-fit">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-bold text-gray-900">Order Details</h3>
              <button onClick={() => setSelected(null)} className="p-1.5 text-gray-400 hover:bg-gray-50 rounded-lg transition-colors"><X size={14} /></button>
            </div>
            <div className="space-y-4">
              <div className="p-3.5 bg-gray-50 rounded-xl">
                <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Order Reference</p>
                <p className="text-sm font-mono font-black text-gray-900">{selected.id}</p>
              </div>
              <div className="space-y-2.5">
                {[["Buyer", selected.buyer], ["Event", selected.event], ["Organizer", selected.organizer], ["Date", selected.date], ["Payment", selected.method]].map(([k, v]) => (
                  <div key={k} className="flex justify-between items-center text-sm">
                    <span className="text-gray-400">{k}</span>
                    <span className="font-semibold text-gray-900 text-right max-w-[160px] truncate">{v}</span>
                  </div>
                ))}
              </div>
              <div className="border-t border-gray-100 pt-4">
                <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Commission Breakdown</p>
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Order Amount</span>
                    <span className="font-bold text-gray-900">{selected.amount}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Platform (10%)</span>
                    <span className="font-bold text-red-400">-{selected.commission}</span>
                  </div>
                  <div className="flex justify-between text-sm border-t border-gray-100 pt-2">
                    <span className="font-bold text-gray-700">Organizer Net</span>
                    <span className="font-black text-[#323891]">
                      ${(parseFloat(selected.amount.replace("$", "")) - parseFloat(selected.commission.replace("$", ""))).toFixed(2)}
                    </span>
                  </div>
                </div>
              </div>
              <Badge status={selected.status} />
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// ─── Admin › Commission Settings ──────────────────────────────────────────────

function CommissionSettings() {
  const [defaultRate, setDefaultRate] = useState("10");
  const [orgs, setOrgs] = useState(allOrgsData);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editVal, setEditVal] = useState("");

  const startEdit = (id: string, current: number | null) => { setEditingId(id); setEditVal(current?.toString() ?? ""); };
  const saveEdit = (id: string) => {
    setOrgs((p) => p.map((o) => o.id === id ? { ...o, override: editVal ? Number(editVal) : null } : o));
    setEditingId(null);
  };

  return (
    <div className="space-y-5">
      <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 className="text-sm font-bold text-gray-900 mb-1">Global Default Commission</h3>
        <p className="text-xs text-gray-400 mb-5">Applied to all organizers unless a per-organizer override is set.</p>
        <div className="flex items-center gap-4">
          <div className="relative w-32">
            <input
              value={defaultRate}
              onChange={(e) => setDefaultRate(e.target.value)}
              className="w-full px-3.5 py-2.5 pr-8 rounded-xl border border-gray-200 bg-gray-50/50 text-sm font-bold text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
            />
            <span className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
          </div>
          <button className="px-4 py-2.5 bg-[#323891] text-white text-sm font-bold rounded-xl hover:bg-[#0255cc] transition-colors shadow-sm shadow-[#323891]/20">
            Save Default
          </button>
          <p className="text-sm text-gray-400">
            Current: <span className="font-bold text-gray-700">{defaultRate}%</span>
          </p>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
          <h3 className="text-sm font-bold text-gray-900">Per-Organizer Overrides</h3>
          <SearchBox placeholder="Search organizers..." small />
        </div>
        <table className="w-full">
          <THead cols={["Organizer", "Contact", "Events", "Revenue", "Commission Rate", "Actions"]} />
          <tbody>
            {orgs.map((o, i) => (
              <tr key={o.id} className={`${i < orgs.length - 1 ? "border-b border-gray-50" : ""} hover:bg-gray-50/50 transition-colors`}>
                <td className="px-4 py-3.5">
                  <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-[#323891]/10 rounded-full flex items-center justify-center text-[#323891] text-[11px] font-black shrink-0">{o.name[0]}</div>
                    <span className="text-sm font-semibold text-gray-900">{o.name}</span>
                  </div>
                </td>
                <td className="px-4 py-3.5 text-sm text-gray-500">{o.contact}</td>
                <td className="px-4 py-3.5 text-sm text-gray-600">{o.events}</td>
                <td className="px-4 py-3.5 text-sm font-bold text-gray-900">{o.revenue}</td>
                <td className="px-4 py-3.5">
                  {editingId === o.id ? (
                    <div className="flex items-center gap-2">
                      <div className="relative w-24">
                        <input
                          value={editVal}
                          onChange={(e) => setEditVal(e.target.value)}
                          placeholder={defaultRate}
                          className="w-full px-3 py-1.5 pr-7 rounded-lg border border-[#323891] bg-white text-sm focus:outline-none"
                          autoFocus
                        />
                        <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs">%</span>
                      </div>
                      <button onClick={() => saveEdit(o.id)} className="p-1.5 bg-[#323891] text-white rounded-lg"><Check size={12} /></button>
                      <button onClick={() => setEditingId(null)} className="p-1.5 text-gray-400 bg-gray-50 rounded-lg"><X size={12} /></button>
                    </div>
                  ) : (
                    <span className={`text-sm font-semibold ${o.override ? "text-[#323891]" : "text-gray-400"}`}>
                      {o.override ? `${o.override}%` : `${defaultRate}% (default)`}
                    </span>
                  )}
                </td>
                <td className="px-4 py-3.5">
                  {editingId !== o.id && (
                    <button
                      onClick={() => startEdit(o.id, o.override)}
                      className="flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                      <Edit2 size={11} />
                      Edit
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Admin › Payout Management ────────────────────────────────────────────────

function PayoutManagement() {
  const [modal, setModal] = useState<typeof payoutRows[0] | null>(null);
  const [ref, setRef] = useState("");
  const [date, setDate] = useState("2026-07-05");
  const [paid, setPaid] = useState<string[]>([]);

  const handleMarkPaid = () => {
    if (modal) { setPaid((p) => [...p, modal.id]); setModal(null); setRef(""); }
  };

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-3 gap-4">
        <StatCard icon={Wallet} label="Total Pending" value="$80,359" change="4 organizers" changeType="neutral" color="bg-amber-500" />
        <StatCard icon={CheckCircle2} label="Paid This Month" value="$72,000" change="Jul 2026" changeType="up" color="bg-[#323891]" />
        <StatCard icon={DollarSign} label="Platform Revenue (Jul)" value="$8,200" change="+12% vs Jun" changeType="up" color="bg-violet-500" />
      </div>

      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-50">
          <h3 className="text-sm font-bold text-gray-900">Pending Payouts</h3>
        </div>
        <table className="w-full">
          <THead cols={["ID", "Organizer", "Events", "Gross Sales", "Pending Balance", "Last Paid", "Status", "Action"]} />
          <tbody>
            {payoutRows.map((row, i) => {
              const isPaid = paid.includes(row.id);
              return (
                <tr key={row.id} className={`${i < payoutRows.length - 1 ? "border-b border-gray-50" : ""} hover:bg-gray-50/50 transition-colors`}>
                  <td className="px-4 py-4 text-[11px] font-mono text-gray-400">{row.id}</td>
                  <td className="px-4 py-4">
                    <div className="flex items-center gap-2.5">
                      <div className="w-7 h-7 bg-[#323891]/10 rounded-full flex items-center justify-center text-[#323891] text-[11px] font-black shrink-0">{row.organizer[0]}</div>
                      <span className="text-sm font-semibold text-gray-900">{row.organizer}</span>
                    </div>
                  </td>
                  <td className="px-4 py-4 text-sm text-gray-600">{row.events}</td>
                  <td className="px-4 py-4 text-sm font-bold text-gray-900">{row.gross}</td>
                  <td className="px-4 py-4 text-sm font-black text-amber-600">{isPaid ? "$0.00" : row.pending}</td>
                  <td className="px-4 py-4 text-sm text-gray-500">{row.lastPaid}</td>
                  <td className="px-4 py-4"><Badge status={isPaid ? "Paid" : "Pending"} /></td>
                  <td className="px-4 py-4">
                    {!isPaid ? (
                      <button
                        onClick={() => setModal(row)}
                        className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-[#323891] hover:bg-[#0255cc] rounded-xl transition-colors shadow-sm shadow-[#323891]/20 whitespace-nowrap"
                      >
                        <CheckCircle2 size={12} />
                        Mark as Paid
                      </button>
                    ) : (
                      <span className="text-xs text-gray-400 font-semibold">Paid Jul 5</span>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {modal && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-[2px] flex items-center justify-center z-50 p-4" onClick={() => setModal(null)}>
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
              <h3 className="font-bold text-gray-900">Confirm Payout</h3>
              <button onClick={() => setModal(null)} className="p-1.5 text-gray-400 hover:bg-gray-50 rounded-xl transition-all"><X size={15} /></button>
            </div>
            <div className="px-6 py-5 space-y-4">
              <div className="p-4 bg-[#323891]/6 border border-[#323891]/15 rounded-xl">
                <p className="text-xs text-gray-500 mb-0.5">Paying out to</p>
                <p className="font-bold text-gray-900 mb-1">{modal.organizer}</p>
                <p className="text-2xl font-black text-[#323891]">{modal.pending}</p>
              </div>
              <div>
                <label className="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Reference Number *</label>
                <Input value={ref} onChange={setRef} placeholder="e.g. TRF-20260705-001" />
              </div>
              <div>
                <label className="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Payment Date</label>
                <input
                  type="date"
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all"
                />
              </div>
            </div>
            <div className="flex items-center gap-3 px-6 py-4 border-t border-gray-100">
              <button onClick={() => setModal(null)} className="flex-1 py-2.5 text-sm font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors">
                Cancel
              </button>
              <button
                onClick={handleMarkPaid}
                disabled={!ref}
                className="flex-1 py-2.5 text-sm font-bold text-white bg-[#323891] hover:bg-[#0255cc] disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-colors"
              >
                Confirm Payment
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Micro helpers ────────────────────────────────────────────────────────────

function SectionHead({ icon: Icon, label, noMargin }: { icon: any; label: string; noMargin?: boolean }) {
  return (
    <div className={`flex items-center gap-2 ${noMargin ? "" : "mb-5"}`}>
      <Icon size={14} className="text-gray-400" />
      <h3 className="text-sm font-bold text-gray-900">{label}</h3>
    </div>
  );
}

function FieldLabel({ children }: { children: React.ReactNode }) {
  return <label className="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">{children}</label>;
}

function SelectField({ options }: { options: string[] }) {
  return (
    <div className="relative">
      <select className="w-full px-3.5 py-2.5 rounded-lg border border-gray-200 bg-gray-50/50 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all appearance-none">
        {options.map((o) => <option key={o}>{o}</option>)}
      </select>
      <ChevronDown size={13} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
    </div>
  );
}

function SelectSmall({ options }: { options: string[] }) {
  return (
    <div className="relative">
      <select className="pl-3 pr-7 py-2 text-sm bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all appearance-none text-gray-600">
        {options.map((o) => <option key={o}>{o}</option>)}
      </select>
      <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
    </div>
  );
}

function SearchBox({ placeholder, small }: { placeholder: string; small?: boolean }) {
  return (
    <div className="relative">
      <Search size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
      <input
        className={`pl-8 pr-4 py-2 text-sm bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#323891]/20 focus:border-[#323891] transition-all placeholder:text-gray-400 ${small ? "w-40" : "w-48"}`}
        placeholder={placeholder}
      />
    </div>
  );
}

function IconBtn({
  icon: Icon,
  hoverColor = "text-gray-600 hover:bg-gray-50",
}: {
  icon: any;
  hoverColor?: string;
}) {
  return (
    <button className={`p-1.5 text-gray-300 ${hoverColor} rounded-lg transition-all`}>
      <Icon size={14} />
    </button>
  );
}

function InfoBox({ label, value, mono }: { label: string; value: string; mono?: boolean }) {
  return (
    <div className="p-3 bg-gray-50 rounded-xl">
      <p className="text-[10px] text-gray-400 mb-0.5">{label}</p>
      <p className={`text-sm font-semibold text-gray-700 ${mono ? "font-mono" : ""}`}>{value}</p>
    </div>
  );
}

// ─── App ──────────────────────────────────────────────────────────────────────

export default function App() {
  const [role, setRole] = useState<Role | null>(null);
  const [screen, setScreen] = useState<Screen>("dashboard");

  if (!role) {
    return <LoginScreen onLogin={(r) => { setRole(r); setScreen("dashboard"); }} />;
  }

  const r = role;

  function renderScreen() {
    if (r === "organizer") {
      switch (screen) {
        case "dashboard": return <OrgDashboard />;
        case "create-event": return <CreateEvent />;
        case "my-events": return <MyEvents />;
        case "earnings": return <Earnings />;
        default: return <OrgDashboard />;
      }
    } else {
      switch (screen) {
        case "dashboard": return <AdminDashboard />;
        case "org-approvals": return <OrgApprovals />;
        case "orders-payments": return <OrdersPayments />;
        case "commission": return <CommissionSettings />;
        case "payouts": return <PayoutManagement />;
        default: return <AdminDashboard />;
      }
    }
  }

  return (
    <div className="flex h-screen overflow-hidden bg-[#f4f6f8]">
      <Sidebar
        role={r}
        screen={screen}
        setScreen={setScreen}
        onLogout={() => { setRole(null); setScreen("dashboard"); }}
      />
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <TopBar screen={screen} role={r} />
        <main className="flex-1 overflow-y-auto p-6">
          {renderScreen()}
        </main>
      </div>
    </div>
  );
}
