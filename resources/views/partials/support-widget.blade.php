{{-- Ekaadh support widget: FAQ first, then live chat with admin --}}
<div x-data="ekaadhSupport({
    locale: @js(app()->getLocale()),
    csrf: @js(csrf_token()),
    routes: {
        faqs: @js(route('support.faqs')),
        conversation: @js(route('support.conversation')),
        messages: @js(url('/support/conversations/__ID__/messages')),
        send: @js(url('/support/conversations/__ID__/messages')),
    },
    labels: {
        title: @js(__('ui.support_title')),
        faq: @js(__('ui.support_faq')),
        chat: @js(__('ui.support_chat')),
        placeholder: @js(__('ui.support_message_placeholder')),
        send: @js(__('ui.support_send')),
        talkToHuman: @js(__('ui.support_talk_to_human')),
        stillNeedHelp: @js(__('ui.support_still_need_help')),
        loading: @js(__('ui.support_loading')),
        closed: @js(__('ui.support_conversation_closed')),
    },
})" x-cloak class="fixed z-[60] bottom-5 right-5 max-sm:left-4 max-sm:right-4 max-sm:bottom-[max(1.25rem,env(safe-area-inset-bottom))] max-sm:flex max-sm:flex-col max-sm:items-end">
    <button type="button" @click="toggle()"
            class="w-14 h-14 rounded-full bg-brand text-white shadow-xl shadow-brand/30 flex items-center justify-center hover:bg-brand-dark transition-colors"
            :aria-expanded="open">
        <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>

    <div x-show="open" x-transition
         class="absolute bottom-16 right-0 w-[min(100vw-2rem,380px)] max-sm:w-full h-[min(70vh,540px)] bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col">
        <div class="px-4 py-3 bg-ink text-white flex items-center justify-between gap-2">
            <div class="min-w-0">
                <div class="font-bold text-sm truncate" x-text="labels.title"></div>
                <div class="text-[11px] text-slate-300">Ekaadh</div>
            </div>
            <div class="flex rounded-lg bg-white/10 p-0.5 text-[11px] font-bold shrink-0">
                <button type="button" @click="tab = 'faq'" :class="tab === 'faq' ? 'bg-white text-ink' : 'text-slate-300'" class="px-2.5 py-1 rounded-md" x-text="labels.faq"></button>
                <button type="button" @click="openChat()" :class="tab === 'chat' ? 'bg-white text-ink' : 'text-slate-300'" class="px-2.5 py-1 rounded-md" x-text="labels.chat"></button>
            </div>
        </div>

        <div x-show="tab === 'faq'" class="flex-1 overflow-y-auto p-4 space-y-2">
            <template x-if="loadingFaqs">
                <p class="text-sm text-mute" x-text="labels.loading"></p>
            </template>
            <template x-for="item in faqs" :key="item.id">
                <div class="rounded-xl border border-slate-100 overflow-hidden">
                    <button type="button" @click="toggleFaq(item)" class="w-full text-left px-3 py-3 text-sm font-bold hover:bg-slate-50" x-text="item.question"></button>
                    <div x-show="expandedFaq === item.id" class="px-3 pb-3 text-sm text-mute whitespace-pre-line border-t border-slate-50 pt-2" x-text="item.answer"></div>
                </div>
            </template>
            <button type="button" @click="openChat(true)" class="w-full mt-3 py-3 rounded-xl bg-brand text-white text-sm font-bold" x-text="labels.talkToHuman"></button>
        </div>

        <div x-show="tab === 'chat'" class="flex-1 flex flex-col min-h-0">
            <div class="flex-1 overflow-y-auto p-4 space-y-2" id="support-chat-scroll">
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.sender_type === 'customer' ? 'flex justify-end' : 'flex justify-start'">
                        <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm whitespace-pre-line"
                             :class="msg.sender_type === 'customer' ? 'bg-brand text-white' : (msg.sender_type === 'system' ? 'bg-slate-100 text-ink border border-slate-200' : 'bg-slate-50 text-ink border border-slate-100')"
                             x-text="msg.body"></div>
                    </div>
                </template>
            </div>
            <div class="border-t border-slate-100 p-3" x-show="conversationStatus === 'open'">
                <form @submit.prevent="sendMessage()" class="flex gap-2">
                    <input x-model="draft" maxlength="2000" :placeholder="labels.placeholder"
                           class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand text-white text-sm font-bold" x-text="labels.send"></button>
                </form>
            </div>
            <div class="border-t border-slate-100 p-3 text-sm text-mute" x-show="conversationStatus === 'closed'" x-text="labels.closed"></div>
        </div>
    </div>
</div>

<script>
function ekaadhSupport(config) {
    return {
        open: false,
        tab: 'faq',
        faqs: [],
        expandedFaq: null,
        loadingFaqs: false,
        conversationId: null,
        conversationStatus: 'open',
        messages: [],
        draft: '',
        pollTimer: null,
        labels: config.labels,

        toggle() {
            this.open = !this.open;
            if (this.open && this.faqs.length === 0) this.loadFaqs();
            if (!this.open) this.stopPoll();
        },

        async loadFaqs() {
            this.loadingFaqs = true;
            try {
                const res = await fetch(`${config.routes.faqs}?locale=${encodeURIComponent(config.locale)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.faqs = data.faqs || [];
            } catch (e) {
                this.faqs = [];
            } finally {
                this.loadingFaqs = false;
            }
        },

        toggleFaq(item) {
            this.expandedFaq = this.expandedFaq === item.id ? null : item.id;
        },

        async openChat(fromFaq = false) {
            this.tab = 'chat';
            if (!this.conversationId) {
                await this.ensureConversation();
            }
            if (fromFaq && this.messages.length === 0) {
                // Gentle nudge — customer can type when FAQ did not help.
            }
            await this.loadMessages();
            this.startPoll();
        },

        async ensureConversation() {
            const res = await fetch(config.routes.conversation, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: '{}',
            });
            const data = await res.json();
            this.conversationId = data.conversation?.id;
            this.conversationStatus = data.conversation?.status || 'open';
        },

        messagesUrl() {
            return config.routes.messages.replace('__ID__', this.conversationId);
        },

        sendUrl() {
            return config.routes.send.replace('__ID__', this.conversationId);
        },

        async loadMessages() {
            if (!this.conversationId) return;
            const since = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
            const res = await fetch(`${this.messagesUrl()}?since=${since}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            const incoming = data.messages || [];
            if (incoming.length) {
                const ids = new Set(this.messages.map(m => m.id));
                incoming.forEach(m => { if (!ids.has(m.id)) this.messages.push(m); });
                this.$nextTick(() => {
                    const el = document.getElementById('support-chat-scroll');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        },

        async sendMessage() {
            const body = this.draft.trim();
            if (!body || !this.conversationId) return;
            this.draft = '';
            const res = await fetch(this.sendUrl(), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: JSON.stringify({ body }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.message) this.messages.push(data.message);
                this.$nextTick(() => {
                    const el = document.getElementById('support-chat-scroll');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        },

        startPoll() {
            this.stopPoll();
            this.pollTimer = setInterval(() => this.loadMessages(), 4000);
        },

        stopPoll() {
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = null;
        },
    };
}
</script>
