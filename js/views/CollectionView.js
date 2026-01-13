// Fichier de la vue de collection
import HUD from '/anime_game_card/js/components/HUD.js';
import Card from '../components/Card.js';

export default {
    components: { HUD, Card },
    template: `
    <div class="collection-view">
        <HUD :user="user" @navigate="$emit('change-view', $event)" @logout="logout" />

        <button class="btn-back-hub" @click="$emit('change-view', 'HubView')">
            ⬅ Retour au QG
        </button>

        <div class="book-container">
            <div class="book-page left-page">
                <div class="filters">
                    <div class="filter-group">
                        <input v-model="search" placeholder="Rechercher..." class="search-input">
                        <button 
                            @click="showOwnedOnly = !showOwnedOnly" 
                            class="filter-btn owned-btn" 
                            :class="{active: showOwnedOnly}">
                            {{ showOwnedOnly ? '✅ Possédées' : '🟦 Toutes' }}
                        </button>
                    </div>
                    <div class="filter-group type-filters">
                        <button @click="filterType='All'" class="filter-btn" :class="{active: filterType=='All'}">Tout</button>
                        <button @click="filterType='Unit'" class="filter-btn" :class="{active: filterType=='Unit'}">Unités</button>
                        <button @click="filterType='Spell'" class="filter-btn" :class="{active: filterType=='Spell'}">Sorts</button>
                    </div>
                </div>

                <div class="cards-grid">
                    <div v-for="card in paginatedCards" 
                         :key="card.id" 
                         class="card-wrapper"
                         :draggable="card.user_quantity > 0" 
                         @dragstart="startDrag($event, card)"
                         @click="addToDeck(card)">
                         <Card :card="card" />
                    </div>
                </div>
                
                <div class="pagination">
                    <button class="nav-btn" @click="page--" :disabled="page <= 1">◀</button>
                    <span class="page-info">Page {{ page }} / {{ totalPages }}</span>
                    <button class="nav-btn" @click="page++" :disabled="page >= totalPages">▶</button>
                </div>
            </div>

            <div class="book-page right-page" 
                 @dragover.prevent 
                 @drop="handleDrop">
                
                <div class="deck-tabs">
                    <button @click="activeTab = 'list'" class="tab-btn" :class="{active: activeTab=='list'}">Mes Grimoires</button>
                    <button @click="activeTab = 'edit'" class="tab-btn" :class="{active: activeTab=='edit'}" :disabled="!currentDeck">Édition</button>
                    <button @click="activeTab = 'new'" class="tab-btn" :class="{active: activeTab=='new'}">Nouveau +</button>
                </div>

                <div v-if="activeTab === 'list'" class="deck-list-container">
                    <h3>Mes Decks</h3>
                    <div v-for="deck in decks" 
                         :key="deck.id" 
                         class="deck-item" 
                         :class="{ burning: deletingId === deck.id }"
                         @click="selectDeck(deck)">
                        <div class="deck-icon">📘</div>
                        <div class="deck-info">
                            <span class="deck-name">{{ deck.name }}</span>
                            <span class="deck-date">{{ new Date(deck.created_at).toLocaleDateString() }}</span>
                        </div>
                        <button class="btn-delete" @click.stop="deleteDeck(deck.id)">🗑️</button>
                    </div>
                </div>

                <div v-else-if="activeTab === 'edit'" class="deck-editor">
                    <div class="editor-header">
                        <input v-model="currentDeck.name" class="deck-name-input">
                        <div class="deck-count" :class="{full: totalCards === 50}">{{ totalCards }} / 50</div>
                    </div>

                    <div class="deck-cards-list">
                        <p v-if="deckCards.length === 0" class="empty-msg">Glissez des cartes ici...</p>
                        <div v-for="card in deckCards" :key="card.id" class="deck-card-item" @contextmenu.prevent="removeFromDeck(card)">
                            <Card :card="card" :small="true" />
                            <button class="btn-remove" @click="removeFromDeck(card)">-</button>
                        </div>
                    </div>

                    <button class="btn-save" @click="saveDeck">💾 SAUVEGARDER</button>
                </div>

                <div v-else class="new-deck-form">
                    <h3>Créer un nouveau Pacte</h3>
                    <input v-model="newDeckName" placeholder="Nom du deck" class="input-glass">
                    <button class="btn-action" @click="createDeck">Créer</button>
                </div>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            cards: [],
            decks: [],
            currentDeck: null,
            deckCards: [],
            activeTab: 'list',
            page: 1,
            cardsPerPage: 8,
            search: '',
            filterType: 'All',
            showOwnedOnly: false,
            newDeckName: '',
            deletingId: null
        }
    },
    computed: {
        // Filtrage des cartes
        filteredCards() {
            return this.cards.filter(c => {
                const matchSearch = c.name.toLowerCase().includes(this.search.toLowerCase());
                const matchType = this.filterType === 'All' || c.type === this.filterType;
                const matchOwned = this.showOwnedOnly ? c.user_quantity > 0 : true;
                return matchSearch && matchType && matchOwned;
            });
        },
        // Pagination
        totalPages() { return Math.ceil(this.filteredCards.length / this.cardsPerPage) || 1; },
        paginatedCards() {
            if (this.page > this.totalPages) this.page = 1;
            const start = (this.page - 1) * this.cardsPerPage;
            return this.filteredCards.slice(start, start + this.cardsPerPage);
        },
        totalCards() { return this.deckCards.reduce((acc, c) => acc + c.quantity, 0); }
    },
    mounted() { this.loadCollection(); },
    methods: {
        logout() { },
        async loadCollection() {
            const res = await axios.get('http://localhost/anime_game_card/api/routes.php?action=get_collection', { withCredentials: true });
            this.cards = res.data.cards;
            this.decks = res.data.decks;
        },
        async selectDeck(deck) {
            this.currentDeck = deck;
            const res = await axios.get(`http://localhost/anime_game_card/api/routes.php?action=get_deck_content&id=${deck.id}`, { withCredentials: true });
            this.deckCards = res.data.cards.map(c => ({ ...c, quantity: c.quantity }));
            this.activeTab = 'edit';
        },
        async createDeck() {
            if (!this.newDeckName) return;
            const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=create_deck', { name: this.newDeckName }, { withCredentials: true });
            if (res.status === 201) {
                this.loadCollection();
                this.newDeckName = '';
                this.activeTab = 'list';
            }
        },

        // Suppression d'un deck avec animation
        async deleteDeck(id) {
            if (confirm("Brûler ce grimoire ?")) {
                this.deletingId = id;

                // On attend 1s (la durée de l'animation) avant de supprimer pour de vrai
                setTimeout(async () => {
                    await axios.get(`http://localhost/anime_game_card/api/routes.php?action=delete_deck&id=${id}`, { withCredentials: true });
                    this.loadCollection();
                    if (this.currentDeck && this.currentDeck.id === id) {
                        this.currentDeck = null;
                        this.activeTab = 'list';
                    }
                    this.deletingId = null;
                }, 1000);
            }
        },
        async saveDeck() {
            if (!this.currentDeck) return;
            await axios.post('http://localhost/anime_game_card/api/routes.php?action=save_deck', {
                deck_id: this.currentDeck.id,
                cards: this.deckCards
            }, { withCredentials: true });
            alert("Deck Sauvegardé !");
        },

        // Drag & Drop
        startDrag(evt, card) {
            if (card.user_quantity > 0) {
                evt.dataTransfer.dropEffect = 'copy';
                evt.dataTransfer.effectAllowed = 'copy';
                evt.dataTransfer.setData('cardID', card.id.toString());
            }
        },
        // Gestion du drop
        handleDrop(evt) {
            const cardID = evt.dataTransfer.getData('cardID');
            const card = this.cards.find(c => c.id == cardID);

            if (card) {
                if (this.activeTab !== 'edit' && this.currentDeck) {
                    this.activeTab = 'edit';
                }
                this.addToDeck(card);
            }
        },
        // Ajout d'une carte au deck
        addToDeck(card) {
            if (!this.currentDeck) {
                alert("Veuillez d'abord sélectionner ou créer un deck !");
                return;
            }

            // Vérification Possession réelle
            if (card.user_quantity <= 0) {
                alert("Vous ne possédez pas cette carte !");
                return;
            }

            // Vérification Quantité Deck vs Quantité Collection
            const existing = this.deckCards.find(c => c.id === card.id);
            const inDeckCount = existing ? existing.quantity : 0;

            if (inDeckCount >= card.user_quantity) {
                alert(`Vous n'avez que ${card.user_quantity} exemplaire(s) de cette carte.`);
                return;
            }

            // Limite Totale 50 cartes
            if (this.totalCards >= 50) return alert("Deck complet (50 cartes max)");

            // Limitation par rareté
            let maxCopies = 3;
            if (card.rarity === 'Legendary') maxCopies = 2;
            if (card.rarity === 'Mythic') maxCopies = 1;

            if (existing) {
                if (existing.quantity < maxCopies) {
                    existing.quantity++;
                } else {
                    alert(`Limite atteinte pour cette rareté (${maxCopies}).`);
                }
            } else {
                this.deckCards.push({ ...card, quantity: 1 });
            }
        },
        // Suppression d'une carte du deck
        removeFromDeck(card) {
            const index = this.deckCards.indexOf(card);
            if (card.quantity > 1) {
                card.quantity--;
            } else {
                this.deckCards.splice(index, 1);
            }
        }
    }
}