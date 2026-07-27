import { useState, useRef, useEffect } from "react";
import {
  Search, MapPin, Calendar, ChevronDown, ChevronRight,
  Check, Download, Share2, Shield, X, Menu,
  ChevronLeft, Facebook, Twitter, Instagram,
  Mail, CheckCircle2, Phone, Ticket, Users,
  Zap, SlidersHorizontal, Smartphone, LogOut, User,
} from "lucide-react";

// ── Types ──────────────────────────────────────────────────────────────────

type Page = "home" | "browse" | "event" | "checkout" | "confirmation" | "tickets" | "organizers";

interface TicketType {
  name: string;
  price: number;
  available: number;
}

interface EventData {
  id: number;
  title: string;
  category: string;
  date: string;
  time: string;
  venue: string;
  city: string;
  price: number;
  image: string;
  featured: boolean;
  description: string;
  organizer: string;
  ticketTypes: TicketType[];
  tags: string[];
}

// ── Data ───────────────────────────────────────────────────────────────────

const EVENTS: EventData[] = [
  {
    id: 1,
    title: "Hargeisa Jazz Night 2026",
    category: "Concerts",
    date: "Fri, 18 Jul 2026",
    time: "7:00 PM",
    venue: "Mansoor Hotel Hall, Hargeisa",
    city: "Hargeisa",
    price: 15,
    image:
      "https://images.unsplash.com/photo-1781446989517-89a3c18b1b29?w=800&h=500&fit=crop&auto=format",
    featured: true,
    description:
      "An unforgettable evening of smooth jazz featuring top East African artists performing live at the iconic Mansoor Hotel Hall. Enjoy world-class music, fine dining, and a vibrant atmosphere in the heart of Hargeisa. The evening features three headline acts including internationally-recognised Somali jazz composer Ayan Ibrahim and her quartet, alongside rising stars from the region. Doors open at 6:00 PM.",
    organizer: "Hargeisa Arts Collective",
    ticketTypes: [
      { name: "General Admission", price: 15, available: 200 },
      { name: "VIP Table (2 seats)", price: 45, available: 30 },
      { name: "Premium Booth (4 seats)", price: 120, available: 10 },
    ],
    tags: ["Jazz", "Live Music", "Networking"],
  },
  {
    id: 2,
    title: "Hargeisa Business Summit 2026",
    category: "Conferences",
    date: "Mon, 4 Aug 2026",
    time: "9:00 AM",
    venue: "Rays Hotel Conference Centre, Hargeisa",
    city: "Hargeisa",
    price: 50,
    image:
      "https://images.unsplash.com/photo-1550305080-4e029753abcf?w=800&h=500&fit=crop&auto=format",
    featured: true,
    description:
      "Somaliland's premier annual business conference bringing together entrepreneurs, investors, and policymakers to shape the future of the Horn of Africa's fastest-growing economy. Two full days of keynotes, panels, and networking sessions with over 80 speakers from 15 countries. Topics include trade, fintech, infrastructure, and youth employment.",
    organizer: "Somaliland Chamber of Commerce",
    ticketTypes: [
      { name: "Standard Pass", price: 50, available: 300 },
      { name: "VIP Pass (incl. gala dinner)", price: 150, available: 50 },
      { name: "Corporate Table (10 seats)", price: 800, available: 15 },
    ],
    tags: ["Business", "Networking", "Entrepreneurship"],
  },
  {
    id: 3,
    title: "Berbera Cultural Show",
    category: "Cultural Shows",
    date: "Sat, 26 Jul 2026",
    time: "5:00 PM",
    venue: "Berbera Waterfront Amphitheatre",
    city: "Berbera",
    price: 8,
    image:
      "https://images.unsplash.com/photo-1764670274687-ab62458d6306?w=800&h=500&fit=crop&auto=format",
    featured: false,
    description:
      "A vibrant celebration of Somali cultural heritage featuring traditional music, poetry, storytelling, and folk dance performances by acclaimed local artists. A must-attend for families and culture enthusiasts set against the stunning backdrop of the Berbera waterfront.",
    organizer: "Berbera Heritage Foundation",
    ticketTypes: [
      { name: "General Admission", price: 8, available: 500 },
      { name: "Family Pack (4 people)", price: 25, available: 100 },
      { name: "Front Row Seating", price: 20, available: 40 },
    ],
    tags: ["Culture", "Traditional", "Family-Friendly"],
  },
  {
    id: 4,
    title: "Somaliland Premier League Final",
    category: "Sports",
    date: "Sun, 10 Aug 2026",
    time: "4:00 PM",
    venue: "Hargeisa Stadium",
    city: "Hargeisa",
    price: 5,
    image:
      "https://images.unsplash.com/photo-1430232324554-8f4aebd06683?w=800&h=500&fit=crop&auto=format",
    featured: true,
    description:
      "The biggest football match of the year — the Somaliland Premier League Championship Final. Watch the two best clubs in the country compete for the coveted trophy in front of 15,000 passionate fans. Gates open at 2:00 PM. Arrive early to avoid queues.",
    organizer: "Somaliland Football Federation",
    ticketTypes: [
      { name: "General Stand", price: 5, available: 8000 },
      { name: "Covered Stand", price: 12, available: 3000 },
      { name: "VIP Box", price: 35, available: 200 },
    ],
    tags: ["Football", "Sports", "Championship"],
  },
  {
    id: 5,
    title: "East Africa Music Festival",
    category: "Concerts",
    date: "Sat, 23 Aug 2026",
    time: "6:00 PM",
    venue: "Maansoor Grounds, Hargeisa",
    city: "Hargeisa",
    price: 20,
    image:
      "https://images.unsplash.com/photo-1778847195158-18f3137a07a8?w=800&h=500&fit=crop&auto=format",
    featured: false,
    description:
      "Three stages, twelve acts, one unforgettable night. The East Africa Music Festival returns to Hargeisa with top artists from Somalia, Ethiopia, Djibouti, and Kenya performing across curated stages. Food vendors, art installations, and cultural exhibits complete the experience.",
    organizer: "SoundBridge Productions",
    ticketTypes: [
      { name: "General Admission", price: 20, available: 2000 },
      { name: "Early Bird (Limited)", price: 15, available: 50 },
      { name: "VIP Experience", price: 75, available: 100 },
    ],
    tags: ["Music", "Festival", "East Africa"],
  },
  {
    id: 6,
    title: "Mogadishu Entrepreneurs Forum",
    category: "Business",
    date: "Thu, 28 Aug 2026",
    time: "10:00 AM",
    venue: "Sky Hotel Conference Hall, Mogadishu",
    city: "Mogadishu",
    price: 40,
    image:
      "https://images.unsplash.com/photo-1744973149087-179e3ed54eae?w=800&h=500&fit=crop&auto=format",
    featured: false,
    description:
      "A one-day intensive forum connecting young entrepreneurs across the Horn of Africa with mentors, investors, and peers. Pitch competitions, masterclasses, and curated networking sessions. This year's theme: Scaling Locally, Competing Globally.",
    organizer: "Horn Startup Network",
    ticketTypes: [
      { name: "Delegate Pass", price: 40, available: 200 },
      { name: "Startup Exhibitor (incl. table)", price: 120, available: 20 },
    ],
    tags: ["Business", "Startups", "Networking"],
  },
];

const CATEGORIES = ["All", "Concerts", "Conferences", "Cultural Shows", "Sports", "Business"];

const CAT_COLORS: Record<string, string> = {
  Concerts: "bg-purple-100 text-purple-700",
  Conferences: "bg-sky-100 text-sky-700",
  "Cultural Shows": "bg-amber-100 text-amber-700",
  Sports: "bg-green-100 text-green-700",
  Business: "bg-violet-100 text-violet-700",
};

// ── Shared Utilities ───────────────────────────────────────────────────────

function CategoryBadge({ category }: { category: string }) {
  return (
    <span
      className={`inline-block text-xs font-bold px-2.5 py-1 rounded-full ${
        CAT_COLORS[category] ?? "bg-gray-100 text-gray-600"
      }`}
    >
      {category}
    </span>
  );
}

function QRCodePlaceholder({ size = 120 }: { size?: number }) {
  const rows = [
    "1111111011010111111",
    "1000001001001000001",
    "1011101010101011101",
    "1011101001001011101",
    "1011101011001011101",
    "1000001010101000001",
    "1111111010101111111",
    "0000000011000000000",
    "1011010101101011011",
    "0101101010010110100",
    "1010011101101001010",
    "0000000011010101001",
    "1111111001100101011",
    "1000001010001010001",
    "1011101010110101010",
    "1011101001001000110",
    "1011101011011010001",
    "1000001010100101010",
    "1111111010101011101",
  ];
  const cols = rows[0].length;
  const cellW = size / cols;
  const cellH = size / rows.length;
  return (
    <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
      <rect width={size} height={size} fill="white" />
      {rows.map((row, r) =>
        row.split("").map((cell, c) =>
          cell === "1" ? (
            <rect
              key={`${r}-${c}`}
              x={c * cellW}
              y={r * cellH}
              width={cellW}
              height={cellH}
              fill="#0f1a2e"
            />
          ) : null
        )
      )}
    </svg>
  );
}

function EventCard({
  event,
  onView,
}: {
  event: EventData;
  onView: () => void;
}) {
  return (
    <div
      className="bg-card rounded-2xl overflow-hidden border border-border shadow-sm hover:shadow-lg transition-all duration-200 group cursor-pointer"
      onClick={onView}
    >
      <div className="relative h-44 overflow-hidden bg-slate-200">
        <img
          src={event.image}
          alt={event.title}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
        <div className="absolute top-3 left-3">
          <CategoryBadge category={event.category} />
        </div>
        {event.featured && (
          <div className="absolute top-3 right-3 bg-primary text-white text-xs font-bold px-2.5 py-1 rounded-full">
            FEATURED
          </div>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-bold text-foreground text-sm leading-snug mb-2.5 line-clamp-2">
          {event.title}
        </h3>
        <div className="space-y-1 mb-3">
          <div className="flex items-center gap-1.5 text-muted-foreground text-xs">
            <Calendar className="w-3.5 h-3.5 flex-shrink-0" />
            <span>
              {event.date} · {event.time}
            </span>
          </div>
          <div className="flex items-center gap-1.5 text-muted-foreground text-xs">
            <MapPin className="w-3.5 h-3.5 flex-shrink-0" />
            <span className="line-clamp-1">{event.venue}</span>
          </div>
        </div>
        <div className="flex items-center justify-between">
          <p className="text-sm">
            <span className="text-muted-foreground text-xs">From </span>
            <span className="font-extrabold text-foreground">${event.price}</span>
          </p>
          <button
            onClick={(e) => {
              e.stopPropagation();
              onView();
            }}
            className="bg-primary hover:bg-[#024ec0] text-white text-xs font-bold px-3.5 py-1.5 rounded-lg transition-colors"
          >
            Get Tickets
          </button>
        </div>
      </div>
    </div>
  );
}

// ── Navbar ─────────────────────────────────────────────────────────────────

function Navbar({
  setPage,
  authed,
  onSignIn,
  onSignOut,
}: {
  setPage: (p: Page) => void;
  authed: boolean;
  onSignIn: () => void;
  onSignOut: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);
  const profileRef = useRef<HTMLDivElement>(null);

  // Close profile dropdown on outside click
  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (profileRef.current && !profileRef.current.contains(e.target as Node)) {
        setProfileOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  return (
    <nav className="sticky top-0 z-50 bg-[#0f1a2e] shadow-xl">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          {/* Logo */}
          <button
            onClick={() => setPage("home")}
            className="flex items-center gap-2.5 flex-shrink-0"
          >
            <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
              <Ticket className="w-4 h-4 text-white" />
            </div>
            <span className="text-white font-extrabold text-xl tracking-tight">
              Ekaadh
            </span>
          </button>

          {/* Centre links */}
          <div className="hidden md:flex items-center gap-7">
            <button
              onClick={() => setPage("browse")}
              className="text-slate-300 hover:text-white text-sm font-medium transition-colors"
            >
              Browse Events
            </button>
            <button
              onClick={() => setPage("organizers")}
              className="text-slate-300 hover:text-white text-sm font-medium transition-colors"
            >
              For Organizers
            </button>
          </div>

          {/* Right side — conditional on auth state */}
          <div className="hidden md:flex items-center gap-3">
            {authed ? (
              <>
                {/* My Tickets */}
                <button
                  onClick={() => setPage("tickets")}
                  className="flex items-center gap-1.5 text-slate-300 hover:text-white text-sm font-medium transition-colors px-1"
                >
                  <Ticket className="w-4 h-4" />
                  My Tickets
                </button>

                {/* Profile dropdown */}
                <div className="relative" ref={profileRef}>
                  <button
                    onClick={() => setProfileOpen(!profileOpen)}
                    className="flex items-center gap-2 bg-white/10 hover:bg-white/15 text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors"
                  >
                    <div className="w-6 h-6 bg-primary rounded-full flex items-center justify-center flex-shrink-0">
                      <User className="w-3.5 h-3.5 text-white" />
                    </div>
                    <span>Faadumo</span>
                    <ChevronDown className={`w-3.5 h-3.5 transition-transform ${profileOpen ? "rotate-180" : ""}`} />
                  </button>

                  {profileOpen && (
                    <div className="absolute right-0 top-full mt-2 w-52 bg-white rounded-xl shadow-xl border border-border overflow-hidden z-50">
                      <div className="px-4 py-3 border-b border-border">
                        <p className="font-bold text-foreground text-sm">Faadumo Hassan</p>
                        <p className="text-xs text-muted-foreground mt-0.5">+252 63 1234567</p>
                      </div>
                      <button
                        onClick={() => { setPage("tickets"); setProfileOpen(false); }}
                        className="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-foreground hover:bg-muted transition-colors"
                      >
                        <Ticket className="w-4 h-4 text-muted-foreground" />
                        My Tickets
                      </button>
                      <button
                        onClick={() => { onSignOut(); setProfileOpen(false); }}
                        className="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                      >
                        <LogOut className="w-4 h-4" />
                        Sign Out
                      </button>
                    </div>
                  )}
                </div>
              </>
            ) : (
              <button
                onClick={onSignIn}
                className="bg-primary hover:bg-[#024ec0] text-white text-sm font-bold px-5 py-2 rounded-lg transition-colors"
              >
                Sign In
              </button>
            )}
          </div>

          {/* Mobile hamburger */}
          <button
            className="md:hidden text-white p-1"
            onClick={() => setOpen(!open)}
          >
            {open ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Mobile menu */}
      {open && (
        <div className="md:hidden bg-[#0a1220] border-t border-white/10 px-4 pb-4 pt-2 space-y-1">
          <button
            onClick={() => { setPage("browse"); setOpen(false); }}
            className="block w-full text-left text-slate-300 hover:text-white py-2.5 text-sm font-medium"
          >
            Browse Events
          </button>
          <button
            onClick={() => { setPage("organizers"); setOpen(false); }}
            className="block w-full text-left text-slate-300 hover:text-white py-2.5 text-sm font-medium"
          >
            For Organizers
          </button>
          {authed ? (
            <>
              <button
                onClick={() => { setPage("tickets"); setOpen(false); }}
                className="block w-full text-left text-slate-300 hover:text-white py-2.5 text-sm font-medium"
              >
                My Tickets
              </button>
              <div className="pt-2 border-t border-white/10 mt-1">
                <p className="text-slate-400 text-xs px-0.5 mb-2">Signed in as Faadumo Hassan</p>
                <button
                  onClick={() => { onSignOut(); setOpen(false); }}
                  className="w-full flex items-center justify-center gap-2 border border-white/20 text-white font-semibold py-2.5 rounded-xl text-sm hover:bg-white/10 transition-colors"
                >
                  <LogOut className="w-4 h-4" />
                  Sign Out
                </button>
              </div>
            </>
          ) : (
            <button
              onClick={() => { onSignIn(); setOpen(false); }}
              className="w-full mt-2 bg-primary text-white font-bold py-3 rounded-xl text-sm"
            >
              Sign In
            </button>
          )}
        </div>
      )}
    </nav>
  );
}

// ── Footer ─────────────────────────────────────────────────────────────────

function Footer({ setPage }: { setPage: (p: Page) => void }) {
  return (
    <footer className="bg-[#0f1a2e] text-slate-400 mt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
          <div>
            <div className="flex items-center gap-2.5 mb-4">
              <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <Ticket className="w-4 h-4 text-white" />
              </div>
              <span className="text-white font-extrabold text-lg tracking-tight">
                Ekaadh
              </span>
            </div>
            <p className="text-sm leading-relaxed mb-5">
              Somaliland's trusted event ticketing platform. Buy tickets instantly with Zaad and eDahab.
            </p>
            <div className="flex gap-2.5">
              {[Facebook, Twitter, Instagram].map((Icon, i) => (
                <a
                  key={i}
                  href="#"
                  className="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition-colors"
                >
                  <Icon className="w-4 h-4 text-white" />
                </a>
              ))}
            </div>
          </div>

          <div>
            <h4 className="text-white font-bold mb-4 text-sm">Discover</h4>
            <ul className="space-y-2 text-sm">
              {["Browse Events", "Concerts", "Conferences", "Cultural Shows", "Sports"].map(
                (l) => (
                  <li key={l}>
                    <button
                      onClick={() => setPage("browse")}
                      className="hover:text-white transition-colors"
                    >
                      {l}
                    </button>
                  </li>
                )
              )}
              <li>
                <button
                  onClick={() => setPage("organizers")}
                  className="hover:text-white transition-colors"
                >
                  For Organizers
                </button>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-white font-bold mb-4 text-sm">Support</h4>
            <ul className="space-y-2 text-sm">
              {[
                "Help Center",
                "Contact Us",
                "Refund Policy",
                "Privacy Policy",
                "Terms of Service",
              ].map((l) => (
                <li key={l}>
                  <a href="#" className="hover:text-white transition-colors">
                    {l}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="text-white font-bold mb-4 text-sm">Contact</h4>
            <ul className="space-y-3 text-sm">
              <li className="flex items-center gap-2">
                <Mail className="w-4 h-4 text-primary flex-shrink-0" />
                <span>support@ekaadh.com</span>
              </li>
              <li className="flex items-center gap-2">
                <Phone className="w-4 h-4 text-primary flex-shrink-0" />
                <span>+252 63 4000000</span>
              </li>
              <li className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-primary flex-shrink-0" />
                <span>Hargeisa, Somaliland</span>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-white/10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <p>© 2026 Ekaadh. All rights reserved.</p>
          <div className="flex items-center gap-4">
            <span className="flex items-center gap-1.5">
              <Shield className="w-3.5 h-3.5 text-primary" />
              Secure Payments
            </span>
            <span className="font-bold text-primary">Zaad</span>
            <span className="text-slate-500">·</span>
            <span className="font-bold text-primary">eDahab</span>
          </div>
        </div>
      </div>
    </footer>
  );
}

// ── HOME PAGE ──────────────────────────────────────────────────────────────

function HomePage({
  setPage,
  setSelectedEvent,
}: {
  setPage: (p: Page) => void;
  setSelectedEvent: (e: EventData) => void;
}) {
  const [activeCat, setActiveCat] = useState("All");
  const scrollRef = useRef<HTMLDivElement>(null);

  const filtered =
    activeCat === "All" ? EVENTS : EVENTS.filter((e) => e.category === activeCat);
  const featured = EVENTS.filter((e) => e.featured);

  const scroll = (dir: "left" | "right") => {
    scrollRef.current?.scrollBy({ left: dir === "left" ? -320 : 320, behavior: "smooth" });
  };

  const goToEvent = (e: EventData) => {
    setSelectedEvent(e);
    setPage("event");
  };

  return (
    <div>
      {/* Hero */}
      <section className="relative min-h-[580px] flex items-center overflow-hidden">
        <div className="absolute inset-0 z-0 bg-[#0a1220]">
          <img
            src="https://images.unsplash.com/photo-1778847195158-18f3137a07a8?w=1600&h=700&fit=crop&auto=format"
            alt="Concert crowd at a live music event"
            className="w-full h-full object-cover opacity-60"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-[#0a1220]/95 via-[#0a1220]/70 to-[#0a1220]/20" />
        </div>
        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 w-full">
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 bg-primary/20 border border-primary/40 text-primary text-xs font-bold px-3 py-1.5 rounded-full mb-6">
              <Zap className="w-3.5 h-3.5" />
              Somaliland&apos;s #1 Ticket Platform
            </div>
            <h1 className="text-5xl sm:text-6xl font-extrabold text-white leading-[1.1] mb-5">
              Find Events<br />
              <span className="text-primary">Near You</span>
            </h1>
            <p className="text-slate-300 text-lg mb-8 leading-relaxed max-w-lg">
              Discover concerts, conferences, cultural shows and more. Pay
              instantly with Zaad or eDahab — no bank card needed.
            </p>

            {/* Search bar */}
            <div className="bg-white rounded-2xl shadow-2xl max-w-2xl flex items-stretch overflow-hidden">
              {/* Keyword */}
              <div className="flex items-center gap-2 flex-1 px-4 py-1 min-w-0">
                <Search className="w-4 h-4 text-slate-400 flex-shrink-0" />
                <input
                  type="text"
                  placeholder="Search events..."
                  className="flex-1 outline-none text-sm text-foreground placeholder-slate-400 bg-transparent min-w-0"
                />
              </div>
              {/* Divider */}
              <div className="w-px bg-slate-200 my-3 flex-shrink-0" />
              {/* Category */}
              <div className="hidden sm:flex items-center gap-1.5 px-3 min-w-[148px] flex-shrink-0">
                <ChevronDown className="w-4 h-4 text-slate-400 flex-shrink-0" />
                <select className="flex-1 outline-none text-sm text-foreground bg-transparent cursor-pointer">
                  <option>All Categories</option>
                  {CATEGORIES.slice(1).map((c) => (
                    <option key={c}>{c}</option>
                  ))}
                </select>
              </div>
              {/* Divider */}
              <div className="hidden sm:block w-px bg-slate-200 my-3 flex-shrink-0" />
              {/* City */}
              <div className="hidden sm:flex items-center gap-1.5 px-3 min-w-[120px] flex-shrink-0">
                <MapPin className="w-4 h-4 text-slate-400 flex-shrink-0" />
                <select className="flex-1 outline-none text-sm text-foreground bg-transparent cursor-pointer">
                  <option>All Cities</option>
                  <option>Hargeisa</option>
                  <option>Berbera</option>
                  <option>Bosaso</option>
                  <option>Mogadishu</option>
                </select>
              </div>
              {/* Search button — always visible, flush right */}
              <button
                onClick={() => setPage("browse")}
                className="bg-primary hover:bg-[#024ec0] text-white font-bold px-6 text-sm flex-shrink-0 transition-colors rounded-r-2xl"
              >
                Search
              </button>
            </div>
          </div>
        </div>
      </section>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {/* Featured / Trending — now first */}
        <section className="pt-10 mb-14">
          <div className="flex items-center gap-3 mb-5">
            <h2 className="text-2xl font-extrabold text-foreground">
              Featured &amp; Trending
            </h2>
            <span className="bg-primary/10 text-primary text-xs font-extrabold px-2.5 py-0.5 rounded-full">
              HOT
            </span>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            {featured.slice(0, 3).map((ev, i) => (
              <div
                key={ev.id}
                className={`relative bg-card rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all cursor-pointer group border border-border ${
                  i === 0 ? "md:col-span-2" : ""
                }`}
                onClick={() => goToEvent(ev)}
              >
                <div className={`relative overflow-hidden bg-slate-200 ${i === 0 ? "h-72" : "h-52"}`}>
                  <img
                    src={ev.image}
                    alt={ev.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                  <div className="absolute top-3 left-3 bg-primary text-white text-xs font-extrabold px-3 py-1 rounded-full">
                    FEATURED
                  </div>
                  <div className="absolute bottom-0 left-0 right-0 p-5">
                    <CategoryBadge category={ev.category} />
                    <h3
                      className={`font-extrabold text-white mt-2 leading-snug ${
                        i === 0 ? "text-2xl" : "text-base"
                      }`}
                    >
                      {ev.title}
                    </h3>
                    <div className="flex flex-wrap items-center gap-3 mt-1.5 text-white/75 text-xs">
                      <span className="flex items-center gap-1">
                        <Calendar className="w-3 h-3" />
                        {ev.date}
                      </span>
                      <span className="flex items-center gap-1">
                        <MapPin className="w-3 h-3" />
                        {ev.city}
                      </span>
                      <span className="font-extrabold text-primary">
                        From ${ev.price}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Category chips */}
        <div className="flex gap-2 overflow-x-auto pb-5 hide-scrollbar">
          {CATEGORIES.map((cat) => (
            <button
              key={cat}
              onClick={() => setActiveCat(cat)}
              className={`flex-shrink-0 px-4 py-2 rounded-full text-sm font-bold transition-all ${
                activeCat === cat
                  ? "bg-primary text-white shadow-sm"
                  : "bg-card text-muted-foreground hover:bg-secondary border border-border"
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Upcoming Events — horizontal scroll with arrow controls */}
        <section className="mb-14">
          <div className="flex items-center justify-between mb-5">
            <h2 className="text-2xl font-extrabold text-foreground">Upcoming Events</h2>
            <div className="flex items-center gap-2">
              <button
                onClick={() => scroll("left")}
                className="w-8 h-8 rounded-full border border-border bg-card flex items-center justify-center hover:bg-muted transition-colors"
              >
                <ChevronLeft className="w-4 h-4 text-foreground" />
              </button>
              <button
                onClick={() => scroll("right")}
                className="w-8 h-8 rounded-full border border-border bg-card flex items-center justify-center hover:bg-muted transition-colors"
              >
                <ChevronRight className="w-4 h-4 text-foreground" />
              </button>
              <button
                onClick={() => setPage("browse")}
                className="text-primary font-bold text-sm hover:underline flex items-center gap-1 ml-1"
              >
                View All <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
          <div
            ref={scrollRef}
            className="flex gap-5 overflow-x-auto pb-3 hide-scrollbar -mx-4 px-4"
          >
            {filtered.map((ev) => (
              <div key={ev.id} className="flex-shrink-0 w-72">
                <EventCard event={ev} onView={() => goToEvent(ev)} />
              </div>
            ))}
            {filtered.length === 0 && (
              <p className="text-muted-foreground text-sm py-8">
                No events in this category yet.
              </p>
            )}
          </div>
        </section>

        {/* How It Works */}
        <section className="mb-16">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-extrabold text-foreground mb-2">
              How It Works
            </h2>
            <p className="text-muted-foreground text-sm">
              Get your tickets in 3 simple steps
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            {[
              {
                icon: Search,
                num: "1",
                title: "Browse Events",
                desc: "Discover hundreds of events across Somaliland — concerts, conferences, cultural shows, and more.",
              },
              {
                icon: Ticket,
                num: "2",
                title: "Buy Instantly",
                desc: "Pay securely with Zaad or eDahab. No bank card needed. Your tickets are confirmed in seconds.",
              },
              {
                icon: CheckCircle2,
                num: "3",
                title: "Get Your QR Ticket",
                desc: "Receive your e-ticket via WhatsApp, SMS, and email. Show the QR code at the door and you're in.",
              },
            ].map(({ icon: Icon, num, title, desc }) => (
              <div
                key={num}
                className="bg-card rounded-2xl p-6 border border-border text-center hover:border-primary/30 transition-colors"
              >
                <div className="relative inline-flex mb-5">
                  <div className="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center">
                    <Icon className="w-6 h-6 text-primary" />
                  </div>
                  <span className="absolute -top-1.5 -right-1.5 w-5 h-5 bg-primary text-white text-xs font-extrabold rounded-full flex items-center justify-center">
                    {num}
                  </span>
                </div>
                <h3 className="font-extrabold text-foreground text-base mb-2">
                  {title}
                </h3>
                <p className="text-muted-foreground text-sm leading-relaxed">
                  {desc}
                </p>
              </div>
            ))}
          </div>
        </section>

        {/* Trust strip */}
        <section className="mb-16">
          <div className="bg-[#0f1a2e] rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
              <h3 className="text-white font-extrabold text-xl mb-1">
                Pay with what you know
              </h3>
              <p className="text-slate-400 text-sm">
                No bank account or credit card required. Just your Zaad or eDahab number.
              </p>
            </div>
            <div className="flex gap-4 flex-shrink-0">
              <div className="bg-white/10 border border-white/20 rounded-xl px-5 py-3 text-center">
                <Smartphone className="w-6 h-6 text-green-400 mx-auto mb-1" />
                <p className="text-white font-extrabold text-sm">Zaad</p>
                <p className="text-slate-400 text-xs">Telesom</p>
              </div>
              <div className="bg-white/10 border border-white/20 rounded-xl px-5 py-3 text-center">
                <Smartphone className="w-6 h-6 text-cyan-400 mx-auto mb-1" />
                <p className="text-white font-extrabold text-sm">eDahab</p>
                <p className="text-slate-400 text-xs">Somtel</p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}

// ── BROWSE PAGE ────────────────────────────────────────────────────────────

function BrowsePage({
  setPage,
  setSelectedEvent,
}: {
  setPage: (p: Page) => void;
  setSelectedEvent: (e: EventData) => void;
}) {
  const [selectedCats, setSelectedCats] = useState<string[]>([]);
  const [sortBy, setSortBy] = useState("Newest");

  const toggleCat = (cat: string) =>
    setSelectedCats((prev) =>
      prev.includes(cat) ? prev.filter((c) => c !== cat) : [...prev, cat]
    );

  const filtered = EVENTS.filter(
    (e) => selectedCats.length === 0 || selectedCats.includes(e.category)
  ).sort((a, b) => {
    if (sortBy === "Price: Low") return a.price - b.price;
    if (sortBy === "Price: High") return b.price - a.price;
    return b.id - a.id;
  });

  const goToEvent = (e: EventData) => {
    setSelectedEvent(e);
    setPage("event");
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-3xl font-extrabold text-foreground mb-1">
          Browse Events
        </h1>
        <p className="text-muted-foreground text-sm">
          Discover what&apos;s happening across Somaliland and the Horn of Africa
        </p>
      </div>

      <div className="flex gap-7">
        {/* Sidebar */}
        <aside className="hidden lg:block w-60 flex-shrink-0">
          <div className="bg-card rounded-2xl border border-border p-5 sticky top-24">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-extrabold text-foreground">Filters</h3>
              {selectedCats.length > 0 && (
                <button
                  onClick={() => setSelectedCats([])}
                  className="text-xs text-primary font-bold hover:underline"
                >
                  Clear
                </button>
              )}
            </div>

            <div className="mb-5">
              <h4 className="text-xs font-extrabold text-muted-foreground uppercase tracking-wider mb-3">
                Category
              </h4>
              <div className="space-y-2">
                {CATEGORIES.slice(1).map((cat) => (
                  <button
                    key={cat}
                    onClick={() => toggleCat(cat)}
                    className="flex items-center gap-2.5 w-full group"
                  >
                    <div
                      className={`w-4 h-4 rounded flex items-center justify-center border-2 flex-shrink-0 transition-colors ${
                        selectedCats.includes(cat)
                          ? "bg-primary border-primary"
                          : "border-border group-hover:border-primary/50"
                      }`}
                    >
                      {selectedCats.includes(cat) && (
                        <Check className="w-2.5 h-2.5 text-white" />
                      )}
                    </div>
                    <span className="text-sm text-foreground group-hover:text-primary transition-colors">
                      {cat}
                    </span>
                  </button>
                ))}
              </div>
            </div>

            <div className="mb-5">
              <h4 className="text-xs font-extrabold text-muted-foreground uppercase tracking-wider mb-3">
                Location
              </h4>
              <div className="space-y-2">
                {["Hargeisa", "Berbera", "Bosaso", "Mogadishu"].map((city) => (
                  <button key={city} className="flex items-center gap-2.5 group w-full">
                    <div className="w-4 h-4 rounded border-2 border-border flex-shrink-0 group-hover:border-primary/50 transition-colors" />
                    <span className="text-sm text-foreground">{city}</span>
                  </button>
                ))}
              </div>
            </div>

            <div>
              <h4 className="text-xs font-extrabold text-muted-foreground uppercase tracking-wider mb-3">
                Date
              </h4>
              <div className="space-y-2">
                {["This Week", "This Month", "Next Month"].map((d) => (
                  <button key={d} className="flex items-center gap-2.5 group w-full">
                    <div className="w-4 h-4 rounded-full border-2 border-border flex-shrink-0 group-hover:border-primary/50 transition-colors" />
                    <span className="text-sm text-foreground">{d}</span>
                  </button>
                ))}
              </div>
            </div>
          </div>
        </aside>

        {/* Main */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center justify-between mb-5">
            <p className="text-sm text-muted-foreground">
              <span className="font-extrabold text-foreground">{filtered.length}</span>{" "}
              events found
            </p>
            <div className="flex items-center gap-2">
              <button className="lg:hidden flex items-center gap-1.5 text-sm font-semibold border border-border rounded-lg px-3 py-1.5 bg-card">
                <SlidersHorizontal className="w-3.5 h-3.5" />
                Filters
              </button>
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="text-sm border border-border rounded-lg px-3 py-1.5 bg-card text-foreground outline-none focus:ring-2 focus:ring-primary/30 cursor-pointer"
              >
                {["Newest", "Date", "Price: Low", "Price: High"].map((s) => (
                  <option key={s}>{s}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
            {filtered.map((ev) => (
              <EventCard key={ev.id} event={ev} onView={() => goToEvent(ev)} />
            ))}
          </div>

          {/* Pagination */}
          <div className="flex items-center justify-center gap-1">
            <button className="w-9 h-9 flex items-center justify-center rounded-lg border border-border text-muted-foreground hover:bg-card transition-colors">
              <ChevronLeft className="w-4 h-4" />
            </button>
            {[1, 2, 3, 4].map((n) => (
              <button
                key={n}
                className={`w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold transition-colors ${
                  n === 1
                    ? "bg-primary text-white"
                    : "border border-border text-muted-foreground hover:bg-card"
                }`}
              >
                {n}
              </button>
            ))}
            <button className="w-9 h-9 flex items-center justify-center rounded-lg border border-border text-muted-foreground hover:bg-card transition-colors">
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── EVENT DETAIL PAGE ──────────────────────────────────────────────────────

function EventDetailPage({
  event,
  setPage,
}: {
  event: EventData;
  setPage: (p: Page) => void;
}) {
  const [quantities, setQuantities] = useState<Record<string, number>>(
    Object.fromEntries(event.ticketTypes.map((t) => [t.name, 0]))
  );

  const total = event.ticketTypes.reduce(
    (sum, t) => sum + (quantities[t.name] || 0) * t.price,
    0
  );
  const totalTickets = Object.values(quantities).reduce((s, q) => s + q, 0);

  const setQty = (name: string, delta: number) =>
    setQuantities((prev) => ({
      ...prev,
      [name]: Math.max(0, Math.min(10, (prev[name] || 0) + delta)),
    }));

  return (
    <div className="pb-24 lg:pb-0">
      {/* Hero */}
      <div className="relative h-72 sm:h-96 bg-[#0a1220]">
        <img
          src={event.image}
          alt={event.title}
          className="w-full h-full object-cover opacity-80"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent" />
        <div className="absolute bottom-0 left-0 right-0 p-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <CategoryBadge category={event.category} />
          <h1 className="text-3xl sm:text-4xl font-extrabold text-white mt-2 mb-3 leading-tight">
            {event.title}
          </h1>
          <div className="flex flex-wrap gap-4 text-white/80 text-sm">
            <span className="flex items-center gap-1.5">
              <Calendar className="w-4 h-4" />
              {event.date} at {event.time}
            </span>
            <span className="flex items-center gap-1.5">
              <MapPin className="w-4 h-4" />
              {event.venue}
            </span>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex gap-8">
          {/* Left column */}
          <div className="flex-1 min-w-0 space-y-5">
            <div className="bg-card rounded-2xl border border-border p-6">
              <h2 className="text-xl font-extrabold text-foreground mb-4">
                About This Event
              </h2>
              <p className="text-muted-foreground leading-relaxed text-sm">
                {event.description}
              </p>
              <div className="flex flex-wrap gap-2 mt-5">
                {event.tags.map((tag) => (
                  <span
                    key={tag}
                    className="text-xs font-semibold bg-secondary text-secondary-foreground px-3 py-1 rounded-full"
                  >
                    #{tag}
                  </span>
                ))}
              </div>
            </div>

            <div className="bg-card rounded-2xl border border-border p-6">
              <h2 className="text-xl font-extrabold text-foreground mb-4">
                Organizer
              </h2>
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center flex-shrink-0">
                  <Users className="w-5 h-5 text-primary" />
                </div>
                <div>
                  <p className="font-bold text-foreground">{event.organizer}</p>
                  <p className="text-sm text-muted-foreground">
                    Verified Organizer
                  </p>
                </div>
              </div>
            </div>

            <div className="bg-card rounded-2xl border border-border p-6">
              <h2 className="text-xl font-extrabold text-foreground mb-4">
                Venue
              </h2>
              <div className="flex items-start gap-3 mb-4">
                <MapPin className="w-5 h-5 text-primary mt-0.5 flex-shrink-0" />
                <div>
                  <p className="font-bold text-foreground">{event.venue}</p>
                  <p className="text-sm text-muted-foreground">
                    {event.city}, Somaliland
                  </p>
                </div>
              </div>
              <div className="h-48 bg-slate-100 rounded-xl flex items-center justify-center border border-border">
                <div className="text-center text-muted-foreground">
                  <MapPin className="w-10 h-10 mx-auto mb-2 text-slate-300" />
                  <p className="text-sm font-medium">Map View</p>
                  <p className="text-xs">{event.venue}</p>
                </div>
              </div>
            </div>

            <div className="bg-card rounded-2xl border border-border p-6">
              <h2 className="text-xl font-extrabold text-foreground mb-4">
                Share Event
              </h2>
              <div className="flex flex-wrap gap-3">
                {[
                  { Icon: Facebook, label: "Facebook", cls: "bg-blue-600 hover:bg-blue-700" },
                  { Icon: Twitter, label: "Twitter", cls: "bg-sky-500 hover:bg-sky-600" },
                  { Icon: Share2, label: "WhatsApp", cls: "bg-green-500 hover:bg-green-600" },
                ].map(({ Icon, label, cls }) => (
                  <button
                    key={label}
                    className={`flex items-center gap-2 ${cls} text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors`}
                  >
                    <Icon className="w-4 h-4" />
                    {label}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Sticky ticket card — desktop */}
          <aside className="hidden lg:block w-80 flex-shrink-0">
            <div className="sticky top-24 bg-card rounded-2xl border border-border p-5 shadow-lg">
              <div className="flex items-center justify-between mb-1">
                <p className="text-xs text-muted-foreground">Starting from</p>
                <span className="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
                  Tickets Available
                </span>
              </div>
              <p className="text-3xl font-extrabold text-foreground mb-5">
                ${event.price}{" "}
                <span className="text-sm font-normal text-muted-foreground">
                  / ticket
                </span>
              </p>

              <div className="space-y-3 mb-4">
                {event.ticketTypes.map((ticket) => (
                  <div
                    key={ticket.name}
                    className="p-3 border border-border rounded-xl"
                  >
                    <div className="flex items-center justify-between mb-1.5">
                      <div>
                        <p className="font-bold text-sm text-foreground">
                          {ticket.name}
                        </p>
                        <p className="text-primary font-extrabold text-sm">
                          ${ticket.price}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => setQty(ticket.name, -1)}
                          className="w-7 h-7 rounded-lg border border-border flex items-center justify-center text-foreground hover:bg-muted text-base font-bold leading-none transition-colors"
                        >
                          −
                        </button>
                        <span className="w-5 text-center text-sm font-bold">
                          {quantities[ticket.name]}
                        </span>
                        <button
                          onClick={() => setQty(ticket.name, 1)}
                          className="w-7 h-7 rounded-lg bg-primary hover:bg-[#024ec0] text-white flex items-center justify-center text-base font-bold leading-none transition-colors"
                        >
                          +
                        </button>
                      </div>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {ticket.available} remaining
                    </p>
                  </div>
                ))}
              </div>

              {total > 0 && (
                <div className="flex items-center justify-between py-3 border-t border-border mb-4">
                  <span className="text-sm font-bold text-foreground">
                    Subtotal ({totalTickets} ticket{totalTickets !== 1 ? "s" : ""})
                  </span>
                  <span className="font-extrabold text-foreground">${total}</span>
                </div>
              )}

              <button
                onClick={() => setPage("checkout")}
                disabled={totalTickets === 0}
                className="w-full bg-primary hover:bg-[#024ec0] disabled:bg-muted disabled:text-muted-foreground text-white font-bold py-3.5 rounded-xl transition-colors text-sm"
              >
                {totalTickets === 0
                  ? "Select Tickets to Continue"
                  : `Proceed to Checkout · $${total}`}
              </button>

              <div className="flex items-center justify-center gap-1.5 mt-3 text-xs text-muted-foreground">
                <Shield className="w-3.5 h-3.5 text-primary" />
                Secure checkout · Zaad &amp; eDahab accepted
              </div>
            </div>
          </aside>
        </div>
      </div>

      {/* Mobile CTA bar */}
      <div className="fixed bottom-0 left-0 right-0 lg:hidden bg-card border-t border-border px-4 py-3 flex items-center gap-3 shadow-xl z-40">
        <div className="flex-shrink-0">
          <p className="text-xs text-muted-foreground">From</p>
          <p className="font-extrabold text-foreground">${event.price}</p>
        </div>
        <button
          onClick={() => setPage("checkout")}
          className="flex-1 bg-primary text-white font-bold py-3 rounded-xl text-sm"
        >
          Get Tickets
        </button>
      </div>
    </div>
  );
}

// ── CHECKOUT PAGE ──────────────────────────────────────────────────────────

function CheckoutPage({
  event,
  setPage,
}: {
  event: EventData;
  setPage: (p: Page) => void;
}) {
  const [step, setStep] = useState(1);
  const [payment, setPayment] = useState<"zaad" | "edahab" | null>(null);

  const orderTotal = event.ticketTypes[0].price * 2;
  const stepLabels = ["Select Tickets", "Your Details", "Payment"];

  return (
    <div className="max-w-2xl mx-auto px-4 sm:px-6 py-8 min-h-[70vh]">
      {/* Progress indicator */}
      <div className="mb-10">
        <div className="flex items-center">
          {stepLabels.map((label, i) => (
            <div key={label} className="flex items-center flex-1 last:flex-none">
              <div className="flex flex-col items-center">
                <div
                  className={`w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-sm transition-all ${
                    i + 1 < step
                      ? "bg-primary text-white"
                      : i + 1 === step
                      ? "bg-primary text-white ring-4 ring-primary/20"
                      : "bg-muted text-muted-foreground"
                  }`}
                >
                  {i + 1 < step ? <Check className="w-4 h-4" /> : i + 1}
                </div>
                <span
                  className={`text-xs font-semibold mt-1.5 hidden sm:block ${
                    i + 1 === step ? "text-primary" : "text-muted-foreground"
                  }`}
                >
                  {label}
                </span>
              </div>
              {i < stepLabels.length - 1 && (
                <div
                  className={`flex-1 h-0.5 mx-2 mb-5 ${
                    i + 1 < step ? "bg-primary" : "bg-muted"
                  }`}
                />
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Step 1 */}
      {step === 1 && (
        <div className="space-y-5">
          <h2 className="text-2xl font-extrabold text-foreground">Order Summary</h2>
          <div className="bg-card rounded-2xl border border-border p-5">
            <div className="flex gap-4 pb-5 mb-5 border-b border-border">
              <div className="w-20 h-20 rounded-xl overflow-hidden bg-slate-200 flex-shrink-0">
                <img
                  src={event.image}
                  alt={event.title}
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="min-w-0">
                <h3 className="font-extrabold text-foreground leading-snug">
                  {event.title}
                </h3>
                <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1.5">
                  <Calendar className="w-3.5 h-3.5" />
                  {event.date} · {event.time}
                </p>
                <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                  <MapPin className="w-3.5 h-3.5" />
                  {event.venue}
                </p>
              </div>
            </div>
            <div className="space-y-3 mb-5">
              <div className="flex items-center justify-between text-sm">
                <div>
                  <p className="font-semibold text-foreground">
                    {event.ticketTypes[0].name}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    ${event.ticketTypes[0].price} × 2
                  </p>
                </div>
                <p className="font-extrabold text-foreground">
                  ${event.ticketTypes[0].price * 2}
                </p>
              </div>
            </div>
            <div className="border-t border-border pt-4 flex items-center justify-between">
              <span className="font-bold text-foreground">Total</span>
              <span className="text-2xl font-extrabold text-foreground">
                ${orderTotal}
              </span>
            </div>
          </div>
          <button
            onClick={() => setStep(2)}
            className="w-full bg-primary hover:bg-[#024ec0] text-white font-bold py-4 rounded-xl transition-colors"
          >
            Continue to Your Details
          </button>
        </div>
      )}

      {/* Step 2 */}
      {step === 2 && (
        <div className="space-y-5">
          <h2 className="text-2xl font-extrabold text-foreground">Your Details</h2>
          <p className="text-sm text-muted-foreground">
            Guest checkout — no account needed. We&apos;ll send your tickets via SMS and WhatsApp.
          </p>
          <div className="bg-card rounded-2xl border border-border p-5 space-y-4">
            <div>
              <label className="block text-sm font-bold text-foreground mb-1.5">
                Full Name
              </label>
              <input
                type="text"
                placeholder="e.g. Faadumo Hassan"
                className="w-full border border-border rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-background"
              />
            </div>
            <div>
              <label className="block text-sm font-bold text-foreground mb-1.5">
                Phone Number{" "}
                <span className="text-primary text-xs font-semibold">
                  (Required for payment)
                </span>
              </label>
              <div className="flex">
                <span className="flex items-center px-3 bg-muted border border-r-0 border-border rounded-l-xl text-sm text-muted-foreground flex-shrink-0">
                  +252
                </span>
                <input
                  type="tel"
                  placeholder="63 1234567"
                  className="flex-1 border border-border rounded-r-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-background"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-bold text-foreground mb-1.5">
                Email Address{" "}
                <span className="text-muted-foreground text-xs font-normal">
                  (Optional)
                </span>
              </label>
              <input
                type="email"
                placeholder="yourname@example.com"
                className="w-full border border-border rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-background"
              />
            </div>
          </div>
          <div className="flex gap-3">
            <button
              onClick={() => setStep(1)}
              className="flex-1 border border-border text-foreground font-bold py-3.5 rounded-xl hover:bg-muted transition-colors"
            >
              Back
            </button>
            <button
              onClick={() => setStep(3)}
              className="flex-1 bg-primary hover:bg-[#024ec0] text-white font-bold py-3.5 rounded-xl transition-colors"
            >
              Continue to Payment
            </button>
          </div>
        </div>
      )}

      {/* Step 3 */}
      {step === 3 && (
        <div className="space-y-5">
          <h2 className="text-2xl font-extrabold text-foreground">Payment</h2>
          <p className="text-sm text-muted-foreground">
            Choose your preferred mobile money method
          </p>

          <div className="grid grid-cols-2 gap-4">
            {/* Zaad */}
            <button
              onClick={() => setPayment("zaad")}
              className={`relative border-2 rounded-2xl p-5 text-left transition-all ${
                payment === "zaad"
                  ? "border-primary bg-primary/5"
                  : "border-border bg-card hover:border-primary/40"
              }`}
            >
              {payment === "zaad" && (
                <div className="absolute top-3 right-3 w-5 h-5 bg-primary rounded-full flex items-center justify-center">
                  <Check className="w-3 h-3 text-white" />
                </div>
              )}
              <div className="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-3">
                <Smartphone className="w-6 h-6 text-green-600" />
              </div>
              <p className="font-extrabold text-foreground text-lg">Zaad</p>
              <p className="text-xs text-muted-foreground mt-0.5">
                Telesom mobile money
              </p>
            </button>

            {/* eDahab */}
            <button
              onClick={() => setPayment("edahab")}
              className={`relative border-2 rounded-2xl p-5 text-left transition-all ${
                payment === "edahab"
                  ? "border-primary bg-primary/5"
                  : "border-border bg-card hover:border-primary/40"
              }`}
            >
              {payment === "edahab" && (
                <div className="absolute top-3 right-3 w-5 h-5 bg-primary rounded-full flex items-center justify-center">
                  <Check className="w-3 h-3 text-white" />
                </div>
              )}
              <div className="w-12 h-12 bg-cyan-50 rounded-xl flex items-center justify-center mb-3">
                <Smartphone className="w-6 h-6 text-cyan-600" />
              </div>
              <p className="font-extrabold text-foreground text-lg">eDahab</p>
              <p className="text-xs text-muted-foreground mt-0.5">
                Somtel mobile money
              </p>
            </button>
          </div>

          {payment && (
            <div className="bg-card rounded-2xl border border-border p-5 space-y-4">
              <p className="text-sm font-bold text-foreground">
                Enter your{" "}
                {payment === "zaad" ? "Zaad (Telesom)" : "eDahab (Somtel)"}{" "}
                number to charge
              </p>
              <div className="flex">
                <span className="flex items-center px-3 bg-muted border border-r-0 border-border rounded-l-xl text-sm text-muted-foreground flex-shrink-0">
                  +252
                </span>
                <input
                  type="tel"
                  placeholder={payment === "zaad" ? "63 XXXXXXX" : "90 XXXXXXX"}
                  className="flex-1 border border-border rounded-r-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-background"
                />
              </div>
              <div className="flex items-center justify-between bg-muted rounded-xl p-4">
                <span className="text-sm font-semibold text-muted-foreground">
                  Total to charge
                </span>
                <span className="text-xl font-extrabold text-foreground">
                  ${orderTotal}
                </span>
              </div>
              <button
                onClick={() => setPage("confirmation")}
                className="w-full bg-primary hover:bg-[#024ec0] text-white font-extrabold py-4 rounded-xl transition-colors text-base"
              >
                Pay ${orderTotal} with{" "}
                {payment === "zaad" ? "Zaad" : "eDahab"}
              </button>
              <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground pt-1">
                <Shield className="w-3.5 h-3.5 text-primary flex-shrink-0" />
                Your payment is encrypted and secure. A confirmation SMS will be sent immediately.
              </div>
            </div>
          )}

          <button
            onClick={() => setStep(2)}
            className="w-full border border-border text-foreground font-bold py-3 rounded-xl hover:bg-muted transition-colors text-sm"
          >
            Back to Details
          </button>
        </div>
      )}
    </div>
  );
}

// ── CONFIRMATION PAGE ──────────────────────────────────────────────────────

function ConfirmationPage({
  event,
  setPage,
}: {
  event: EventData;
  setPage: (p: Page) => void;
}) {
  const ref = "SGJ-2026-49238";

  return (
    <div className="max-w-xl mx-auto px-4 sm:px-6 py-12">
      {/* Success header */}
      <div className="text-center mb-8">
        <div className="relative inline-flex mb-5">
          <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
            <CheckCircle2 className="w-10 h-10 text-green-500" />
          </div>
          <div className="absolute inset-0 rounded-full bg-green-400/20 animate-ping" />
        </div>
        <h1 className="text-3xl font-extrabold text-foreground mb-2">
          Payment Successful!
        </h1>
        <p className="text-muted-foreground text-sm">
          Order reference:{" "}
          <span className="font-extrabold text-foreground">{ref}</span>
        </p>
      </div>

      {/* Order summary */}
      <div className="bg-card rounded-2xl border border-border p-5 mb-4">
        <h2 className="font-extrabold text-foreground text-base mb-4">
          Order Details
        </h2>
        <div className="space-y-3 text-sm">
          {[
            ["Event", event.title],
            ["Date", `${event.date} · ${event.time}`],
            ["Venue", event.venue],
            ["Tickets", `2× ${event.ticketTypes[0].name}`],
            ["Total Paid", `$${event.ticketTypes[0].price * 2}`],
            ["Payment Method", "Zaad"],
          ].map(([label, value]) => (
            <div key={label} className="flex justify-between gap-4">
              <span className="text-muted-foreground flex-shrink-0">{label}</span>
              <span
                className={`font-semibold text-right ${
                  label === "Payment Method"
                    ? "text-green-600"
                    : label === "Total Paid"
                    ? "font-extrabold text-foreground"
                    : "text-foreground"
                }`}
              >
                {value}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Delivery notice */}
      <div className="bg-primary/5 border border-primary/20 rounded-2xl p-4 mb-5 flex items-start gap-3">
        <CheckCircle2 className="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
        <p className="text-sm text-foreground leading-relaxed">
          Your e-tickets have been sent to your <strong>WhatsApp</strong>,{" "}
          <strong>email</strong>, and via <strong>SMS link</strong>. Show the QR code at the venue entrance.
        </p>
      </div>

      {/* Actions */}
      <div className="flex gap-3 mb-8">
        <button
          onClick={() => setPage("tickets")}
          className="flex-1 bg-primary hover:bg-[#024ec0] text-white font-bold py-3.5 rounded-xl transition-colors text-sm"
        >
          View My Tickets
        </button>
        <button className="flex-1 border border-border text-foreground font-bold py-3.5 rounded-xl hover:bg-muted transition-colors text-sm flex items-center justify-center gap-2">
          <Download className="w-4 h-4" />
          Download PDF
        </button>
      </div>

      {/* QR Ticket preview */}
      <div>
        <h2 className="text-base font-extrabold text-foreground mb-3">
          Your Ticket
        </h2>
        <div className="bg-card rounded-2xl overflow-hidden border-2 border-dashed border-primary/30 shadow-sm">
          {/* Ticket header */}
          <div className="relative h-28 bg-[#0f1a2e]">
            <img
              src={event.image}
              alt=""
              className="w-full h-full object-cover opacity-40"
            />
            <div className="absolute inset-0 flex flex-col justify-end p-4">
              <p className="text-white font-extrabold text-base leading-snug">
                {event.title}
              </p>
              <p className="text-white/60 text-xs mt-0.5">
                {event.date} · {event.time}
              </p>
            </div>
          </div>

          {/* Ticket body */}
          <div className="flex">
            <div className="flex-1 p-5">
              <div className="grid grid-cols-2 gap-3 mb-4">
                {[
                  ["Ticket Type", event.ticketTypes[0].name],
                  ["Buyer", "Faadumo Hassan"],
                  ["Venue", event.city],
                  ["Ticket #", "SGJ-001-A"],
                ].map(([label, val]) => (
                  <div key={label}>
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <p className="font-bold text-foreground text-sm">{val}</p>
                  </div>
                ))}
              </div>
              <div className="flex items-center gap-1.5 text-xs font-extrabold text-green-600">
                <div className="w-2 h-2 bg-green-500 rounded-full" />
                VALID
              </div>
            </div>
            <div className="w-32 flex items-center justify-center p-4 border-l-2 border-dashed border-border">
              <div className="rounded-lg overflow-hidden">
                <QRCodePlaceholder size={96} />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── MY TICKETS PAGE ────────────────────────────────────────────────────────

function MyTicketsPage({
  setPage,
  authed,
  onSignIn,
}: {
  setPage: (p: Page) => void;
  authed: boolean;
  onSignIn: () => void;
}) {
  const [expanded, setExpanded] = useState<number | null>(null);

  const myTickets = [
    {
      id: 1,
      event: EVENTS[0],
      ticketType: "VIP Table (2 seats)",
      status: "Valid",
      ticketNumber: "SGJ-001-A",
      buyer: "Faadumo Hassan",
    },
    {
      id: 2,
      event: EVENTS[3],
      ticketType: "Covered Stand",
      status: "Valid",
      ticketNumber: "SGJ-002-B",
      buyer: "Faadumo Hassan",
    },
    {
      id: 3,
      event: EVENTS[1],
      ticketType: "Standard Pass",
      status: "Cancelled",
      ticketNumber: "SGJ-003-C",
      buyer: "Faadumo Hassan",
    },
  ];

  const STATUS: Record<string, string> = {
    Valid: "bg-green-100 text-green-700",
    Used: "bg-gray-100 text-gray-500",
    Cancelled: "bg-red-100 text-red-600",
  };

  if (!authed) {
    return (
      <div className="max-w-md mx-auto px-4 py-16 text-center">
        <div className="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
          <Ticket className="w-8 h-8 text-primary" />
        </div>
        <h1 className="text-2xl font-extrabold text-foreground mb-2">
          My Tickets
        </h1>
        <p className="text-muted-foreground text-sm mb-8">
          Sign in to view your tickets, or look up an order using your reference number.
        </p>
        <div className="space-y-3">
          <button
            onClick={onSignIn}
            className="w-full bg-primary hover:bg-[#024ec0] text-white font-bold py-3.5 rounded-xl transition-colors"
          >
            Sign In with Phone Number
          </button>
          <div className="flex items-center gap-3">
            <div className="flex-1 h-px bg-border" />
            <span className="text-xs text-muted-foreground">OR</span>
            <div className="flex-1 h-px bg-border" />
          </div>
          <div className="bg-card border border-border rounded-2xl p-4 text-left">
            <p className="text-sm font-bold text-foreground mb-3">
              Continue as Guest
            </p>
            <input
              type="text"
              placeholder="Enter order reference (e.g. SGJ-2026-49238)"
              className="w-full border border-border rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary/30 mb-3 bg-background"
            />
            <button
              onClick={onSignIn}
              className="w-full border border-border text-foreground font-bold py-2.5 rounded-xl text-sm hover:bg-muted transition-colors"
            >
              Look Up Order
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-extrabold text-foreground">My Tickets</h1>
          <p className="text-sm text-muted-foreground">
            Faadumo Hassan · +252 63 1234567
          </p>
        </div>
        <span className="text-xs text-muted-foreground bg-green-50 text-green-700 font-semibold px-2.5 py-1 rounded-full">
          Signed in
        </span>
      </div>

      <div className="space-y-4">
        {myTickets.map((t) => (
          <div
            key={t.id}
            className="bg-card border border-border rounded-2xl overflow-hidden"
          >
            <div className="flex items-center gap-4 p-4">
              <div className="w-16 h-16 rounded-xl overflow-hidden bg-slate-200 flex-shrink-0">
                <img
                  src={t.event.image}
                  alt={t.event.title}
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-bold text-foreground text-sm leading-snug line-clamp-1">
                  {t.event.title}
                </p>
                <p className="text-xs text-muted-foreground mt-0.5">
                  {t.event.date} · {t.event.time}
                </p>
                <p className="text-xs text-muted-foreground">{t.ticketType}</p>
              </div>
              <div className="flex flex-col items-end gap-2 flex-shrink-0">
                <span
                  className={`text-xs font-extrabold px-2.5 py-1 rounded-full ${
                    STATUS[t.status]
                  }`}
                >
                  {t.status}
                </span>
                {t.status === "Valid" && (
                  <button
                    onClick={() =>
                      setExpanded(expanded === t.id ? null : t.id)
                    }
                    className="text-xs font-bold text-primary hover:underline"
                  >
                    {expanded === t.id ? "Hide QR" : "View QR"}
                  </button>
                )}
              </div>
            </div>

            {/* Expanded QR */}
            {expanded === t.id && (
              <div className="border-t-2 border-dashed border-border bg-background p-5">
                <div className="flex gap-5 items-start">
                  <div className="flex-shrink-0 text-center">
                    <div className="rounded-xl overflow-hidden border border-border inline-block">
                      <QRCodePlaceholder size={114} />
                    </div>
                    <p className="text-xs text-muted-foreground mt-2 font-mono tracking-wide">
                      {t.ticketNumber}
                    </p>
                  </div>
                  <div className="flex-1 space-y-3">
                    <div>
                      <p className="text-xs text-muted-foreground">Event</p>
                      <p className="font-extrabold text-foreground text-sm leading-snug">
                        {t.event.title}
                      </p>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      {[
                        ["Date", t.event.date],
                        ["Time", t.event.time],
                        ["Venue", t.event.city],
                        ["Type", t.ticketType],
                        ["Buyer", t.buyer],
                      ].map(([label, val]) => (
                        <div key={label}>
                          <p className="text-xs text-muted-foreground">{label}</p>
                          <p className="font-semibold text-foreground text-xs">
                            {val}
                          </p>
                        </div>
                      ))}
                    </div>
                    <div className="flex gap-2 pt-1">
                      <button className="flex items-center gap-1.5 bg-primary text-white text-xs font-bold px-3.5 py-2 rounded-lg">
                        <Download className="w-3.5 h-3.5" />
                        Download
                      </button>
                      <button className="flex items-center gap-1.5 border border-border text-foreground text-xs font-bold px-3.5 py-2 rounded-lg hover:bg-muted transition-colors">
                        <Share2 className="w-3.5 h-3.5" />
                        Share
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        ))}
      </div>

      <div className="mt-8 text-center">
        <button
          onClick={() => setPage("browse")}
          className="text-sm text-primary font-bold hover:underline"
        >
          Browse more events
        </button>
      </div>
    </div>
  );
}

// ── ORGANIZER PAGE ────────────────────────────────────────────────────────

function OrganizerPage({ setPage }: { setPage: (p: Page) => void }) {
  const features = [
    {
      icon: Zap,
      title: "Go Live in Minutes",
      desc: "Create your event, set ticket tiers, and start selling in under 10 minutes. No technical knowledge needed.",
    },
    {
      icon: Smartphone,
      title: "Zaad & eDahab Payouts",
      desc: "Receive your ticket revenue directly to your Zaad or eDahab account within 24 hours of your event.",
    },
    {
      icon: Users,
      title: "Real-Time Attendee Data",
      desc: "Track ticket sales, revenue, and attendee check-ins from your live dashboard as they happen.",
    },
    {
      icon: Shield,
      title: "Secure QR Scanning",
      desc: "Every ticket has a unique QR code. Use our free scanner app at the door to validate tickets instantly.",
    },
    {
      icon: Share2,
      title: "Built-In Promotion",
      desc: "Your event is automatically listed on the Ekaadh homepage and browse page, reaching thousands of buyers.",
    },
    {
      icon: Mail,
      title: "Automated Attendee Comms",
      desc: "We handle confirmation SMS, WhatsApp delivery, and reminder messages to all your ticket buyers automatically.",
    },
  ];

  const plans = [
    {
      name: "Free",
      price: "$0",
      period: "forever",
      desc: "For small community events and first-time organisers.",
      features: [
        "Up to 3 free events per year",
        "Up to 200 tickets per event",
        "Zaad & eDahab payouts",
        "Basic attendee check-in",
        "Email support",
      ],
      cta: "Get Started Free",
      highlight: false,
    },
    {
      name: "Pro",
      price: "$29",
      period: "per event",
      desc: "For professional organisers who need full control and analytics.",
      features: [
        "Unlimited events",
        "Unlimited ticket capacity",
        "Priority listing on homepage",
        "Real-time sales dashboard",
        "Custom ticket types & pricing",
        "Branded confirmation messages",
        "Priority support",
      ],
      cta: "Start Pro Trial",
      highlight: true,
    },
    {
      name: "Enterprise",
      price: "Custom",
      period: "pricing",
      desc: "For festivals, stadiums, and large-scale recurring events.",
      features: [
        "Everything in Pro",
        "Dedicated account manager",
        "White-label ticket pages",
        "API access & integrations",
        "On-site scanning equipment",
        "Revenue share negotiation",
        "SLA guarantee",
      ],
      cta: "Contact Sales",
      highlight: false,
    },
  ];

  const steps = [
    {
      num: "01",
      title: "Create Your Account",
      desc: "Sign up with your phone number. No paperwork, no waiting — your organiser account is active immediately.",
    },
    {
      num: "02",
      title: "Set Up Your Event",
      desc: "Add your event details, upload a cover photo, create ticket tiers with prices, and set your capacity.",
    },
    {
      num: "03",
      title: "Publish & Share",
      desc: "Go live with one click. Share your event link on WhatsApp, social media, or let Ekaadh's audience find you.",
    },
    {
      num: "04",
      title: "Get Paid",
      desc: "Funds land in your Zaad or eDahab account within 24 hours after your event. No hidden fees, no delays.",
    },
  ];

  return (
    <div>
      {/* Hero */}
      <section className="relative bg-[#0f1a2e] overflow-hidden">
        <div className="absolute inset-0">
          <img
            src="https://images.unsplash.com/photo-1550305080-4e029753abcf?w=1600&h=700&fit=crop&auto=format"
            alt="Event organiser at conference"
            className="w-full h-full object-cover opacity-20"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-[#0f1a2e] via-[#0f1a2e]/90 to-transparent" />
        </div>
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 bg-primary/20 border border-primary/30 text-primary text-xs font-bold px-3 py-1.5 rounded-full mb-6">
              <Zap className="w-3.5 h-3.5" />
              For Event Organisers
            </div>
            <h1 className="text-5xl sm:text-6xl font-extrabold text-white leading-[1.1] mb-5">
              Sell Tickets to<br />
              <span className="text-primary">Any Event</span>
            </h1>
            <p className="text-slate-300 text-lg mb-8 leading-relaxed max-w-xl">
              Ekaadh gives you everything you need to create, promote, and manage
              events across Somaliland. Collect payments instantly via Zaad and eDahab.
            </p>
            <div className="flex flex-wrap gap-3">
              <button className="bg-primary hover:bg-[#024ec0] text-white font-extrabold px-7 py-3.5 rounded-xl transition-colors text-sm">
                Create Your First Event
              </button>
              <button className="border border-white/30 hover:border-white/60 text-white font-semibold px-7 py-3.5 rounded-xl transition-colors text-sm">
                See How It Works
              </button>
            </div>

            {/* Stats row */}
            <div className="flex flex-wrap gap-8 mt-12 pt-8 border-t border-white/10">
              {[
                ["2,400+", "Tickets Sold"],
                ["120+", "Events Hosted"],
                ["24h", "Payout Time"],
                ["0%", "Setup Fee"],
              ].map(([val, label]) => (
                <div key={label}>
                  <p className="text-2xl font-extrabold text-primary">{val}</p>
                  <p className="text-sm text-slate-400 mt-0.5">{label}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {/* Features grid */}
        <section className="py-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-extrabold text-foreground mb-3">
              Everything You Need to Run a Successful Event
            </h2>
            <p className="text-muted-foreground max-w-xl mx-auto text-sm leading-relaxed">
              From listing to payout — Ekaadh handles the entire ticketing workflow so you can focus on putting on a great event.
            </p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {features.map(({ icon: Icon, title, desc }) => (
              <div
                key={title}
                className="bg-card rounded-2xl border border-border p-6 hover:border-primary/30 hover:shadow-sm transition-all"
              >
                <div className="w-11 h-11 bg-primary/10 rounded-xl flex items-center justify-center mb-4">
                  <Icon className="w-5 h-5 text-primary" />
                </div>
                <h3 className="font-extrabold text-foreground text-base mb-2">{title}</h3>
                <p className="text-muted-foreground text-sm leading-relaxed">{desc}</p>
              </div>
            ))}
          </div>
        </section>

        {/* How it works */}
        <section className="py-8 mb-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-extrabold text-foreground mb-3">
              From Idea to Sold-Out in 4 Steps
            </h2>
            <p className="text-muted-foreground text-sm">
              No waiting, no paperwork. Start selling today.
            </p>
          </div>
          <div className="relative">
            {/* Connector line */}
            <div className="hidden md:block absolute top-8 left-[calc(12.5%+1rem)] right-[calc(12.5%+1rem)] h-0.5 bg-primary/20" />
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
              {steps.map(({ num, title, desc }) => (
                <div key={num} className="text-center relative">
                  <div className="w-16 h-16 bg-primary text-white font-extrabold text-xl rounded-2xl flex items-center justify-center mx-auto mb-4 relative z-10">
                    {num}
                  </div>
                  <h3 className="font-extrabold text-foreground text-base mb-2">{title}</h3>
                  <p className="text-muted-foreground text-sm leading-relaxed">{desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Pricing */}
        <section className="py-12 mb-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-extrabold text-foreground mb-3">
              Simple, Transparent Pricing
            </h2>
            <p className="text-muted-foreground text-sm">
              Only pay when you sell tickets. No monthly subscriptions for small events.
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            {plans.map((plan) => (
              <div
                key={plan.name}
                className={`rounded-2xl border p-6 relative ${
                  plan.highlight
                    ? "border-primary bg-[#0f1a2e] shadow-2xl scale-[1.02]"
                    : "border-border bg-card"
                }`}
              >
                {plan.highlight && (
                  <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-white text-xs font-extrabold px-4 py-1 rounded-full">
                    MOST POPULAR
                  </div>
                )}
                <h3 className={`font-extrabold text-xl mb-1 ${plan.highlight ? "text-white" : "text-foreground"}`}>
                  {plan.name}
                </h3>
                <div className="flex items-baseline gap-1 mb-2">
                  <span className={`text-4xl font-extrabold ${plan.highlight ? "text-primary" : "text-foreground"}`}>
                    {plan.price}
                  </span>
                  <span className={`text-sm ${plan.highlight ? "text-slate-400" : "text-muted-foreground"}`}>
                    / {plan.period}
                  </span>
                </div>
                <p className={`text-sm mb-6 ${plan.highlight ? "text-slate-400" : "text-muted-foreground"}`}>
                  {plan.desc}
                </p>
                <ul className="space-y-2.5 mb-7">
                  {plan.features.map((f) => (
                    <li key={f} className="flex items-start gap-2.5 text-sm">
                      <div className={`w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 ${
                        plan.highlight ? "bg-primary/20" : "bg-primary/10"
                      }`}>
                        <Check className="w-2.5 h-2.5 text-primary" />
                      </div>
                      <span className={plan.highlight ? "text-slate-300" : "text-foreground"}>
                        {f}
                      </span>
                    </li>
                  ))}
                </ul>
                <button
                  className={`w-full font-bold py-3 rounded-xl text-sm transition-colors ${
                    plan.highlight
                      ? "bg-primary hover:bg-[#024ec0] text-white"
                      : "border border-border text-foreground hover:bg-muted"
                  }`}
                >
                  {plan.cta}
                </button>
              </div>
            ))}
          </div>
        </section>

        {/* Social proof quote */}
        <section className="mb-16">
          <div className="bg-card rounded-2xl border border-border p-8 sm:p-10 flex flex-col sm:flex-row gap-6 items-start">
            <div className="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center flex-shrink-0 text-white font-extrabold text-xl">
              AH
            </div>
            <div>
              <p className="text-foreground text-lg leading-relaxed font-medium mb-3">
                "We sold 800 tickets to our business summit in two days. Ekaadh handled everything — payments, QR codes, check-in. I couldn't believe how smooth it was."
              </p>
              <p className="text-sm font-bold text-foreground">Abdirahman Hassan</p>
              <p className="text-xs text-muted-foreground">Organiser · Hargeisa Business Summit 2026</p>
            </div>
          </div>
        </section>

        {/* Bottom CTA */}
        <section className="mb-16">
          <div className="bg-primary rounded-2xl p-8 sm:p-12 text-center">
            <h2 className="text-3xl font-extrabold text-white mb-3">
              Ready to Sell Your First Ticket?
            </h2>
            <p className="text-white/80 text-sm mb-7 max-w-md mx-auto">
              Join over 120 organisers who trust Ekaadh to power their events across Somaliland.
            </p>
            <div className="flex flex-wrap justify-center gap-3">
              <button className="bg-white text-primary font-extrabold px-8 py-3.5 rounded-xl text-sm hover:bg-slate-50 transition-colors">
                Create Your Event — It's Free
              </button>
              <button
                onClick={() => setPage("browse")}
                className="border border-white/40 text-white font-semibold px-8 py-3.5 rounded-xl text-sm hover:bg-white/10 transition-colors"
              >
                Browse Existing Events
              </button>
            </div>
          </div>
        </section>

      </div>
    </div>
  );
}

// ── APP ────────────────────────────────────────────────────────────────────

export default function App() {
  const [page, setPage] = useState<Page>("home");
  const [selectedEvent, setSelectedEvent] = useState<EventData>(EVENTS[0]);
  // Global auth state: drives navbar and My Tickets gate
  const [authed, setAuthed] = useState(false);

  const navigate = (p: Page) => {
    setPage(p);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const goToEvent = (e: EventData) => {
    setSelectedEvent(e);
    navigate("event");
  };

  const handleSignIn = () => {
    setAuthed(true);
    navigate("tickets");
  };

  const handleSignOut = () => {
    setAuthed(false);
    navigate("home");
  };

  return (
    <div className="min-h-screen bg-background">
      <style>{`
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      `}</style>
      <Navbar
        setPage={navigate}
        authed={authed}
        onSignIn={handleSignIn}
        onSignOut={handleSignOut}
      />
      <main>
        {page === "home" && (
          <HomePage setPage={navigate} setSelectedEvent={goToEvent} />
        )}
        {page === "browse" && (
          <BrowsePage setPage={navigate} setSelectedEvent={goToEvent} />
        )}
        {page === "event" && (
          <EventDetailPage event={selectedEvent} setPage={navigate} />
        )}
        {page === "checkout" && (
          <CheckoutPage event={selectedEvent} setPage={navigate} />
        )}
        {page === "confirmation" && (
          <ConfirmationPage event={selectedEvent} setPage={navigate} />
        )}
        {page === "tickets" && (
          <MyTicketsPage
            setPage={navigate}
            authed={authed}
            onSignIn={handleSignIn}
          />
        )}
        {page === "organizers" && (
          <OrganizerPage setPage={navigate} />
        )}
      </main>
      {page !== "checkout" && page !== "confirmation" && (
        <Footer setPage={navigate} />
      )}
    </div>
  );
}
