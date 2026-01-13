// Fichier de la vue sociale
import HUD from '/anime_game_card/js/components/HUD.js';
import Card from '../components/Card.js';

export default {
    components: { HUD, Card },
    template: `
    <div class="social-view">
        <HUD :user="user" @navigate="$emit('change-view', $event)" @logout="logout" />
        <button class="btn-back-hub" @click="$emit('change-view', 'HubView')">⬅ Retour au QG</button>

        <div class="intranet-container glass-panel">
            <div class="intranet-header">
                <h2>INTRANET ACADÉMIE</h2>
                <div class="social-tabs">
                    <button @click="activeTab = 'friends'" :class="{active: activeTab === 'friends'}">👥 Amis</button>
                    <button @click="activeTab = 'market'" :class="{active: activeTab === 'market'}">⚖️ Marché</button>
                    <button @click="activeTab = 'vortex'" :class="{active: activeTab === 'vortex'}">🌀 Vortex</button>
                </div>
            </div>

            <div v-if="activeTab === 'friends'" class="tab-content friends-tab">
                <div class="add-friend-bar">
                    <input v-model="friendCodeInput" placeholder="Code Ami (ex: Phoenix#1234)" class="input-glass">
                    <button @click="addFriend" class="btn-action">Ajouter</button>
                </div>
                <div class="friends-list">
                    <div v-for="friend in friends" :key="friend.id" class="friend-row">
                        <div class="status-dot online"></div>
                        <img :src="getAvatarUrl(friend.avatar)" class="friend-avatar">
                        <div class="friend-info">
                            <span class="f-name">{{ friend.username }}</span>
                            <span class="f-elo">ELO {{ friend.elo }}</span>
                        </div>
                        <button class="btn-icon">⚔️</button>
                        <button class="btn-icon" @click="removeFriend(friend.id)" style="margin-left: 5px; color: #ff5555;" title="Supprimer">❌</button>
                    </div>
                    <p v-if="friends.length === 0" class="empty-msg">Aucun ami connecté.</p>
                </div>
            </div>

            <div v-if="activeTab === 'market'" class="tab-content market-tab">
                <div class="market-split">
                    <div class="market-buy">
                        <h3>Offres Globales</h3>
                        <div class="market-grid">
                            <div v-for="offer in othersListings" :key="offer.id" class="market-item">
                                <Card :card="{...offer, image_url: offer.image_url, rarity: offer.rarity, cost: offer.cost, attack: offer.attack, hp: offer.hp}" :small="true" />
                                <div class="offer-details">
                                    <span class="seller">Vendeur: {{ offer.seller_name }}</span>
                                    <button class="btn-buy" @click="buyCard(offer)">Acheter {{ offer.price_gold }} 💰</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="market-sell">
                        <div class="my-listings-section" v-if="myListings.length > 0">
                            <h3>Mes Ventes</h3>
                            <div class="my-listings-list">
                                <div v-for="myOffer in myListings" :key="myOffer.id" class="my-listing-row">
                                    <span>{{ myOffer.card_name }} - {{ myOffer.price_gold }}💰</span>
                                    <button class="btn-cancel" @click="cancelSale(myOffer.id)">X</button>
                                </div>
                            </div>
                        </div>

                        <h3>Vendre une carte</h3>
                        <div class="inventory-mini">
                            <div v-for="card in inventory" :key="card.id" class="mini-card" @click="selectForSale(card)" :class="{selected: cardToSell && cardToSell.id === card.id}">
                                <Card :card="card" :small="true" />
                            </div>
                        </div>
                        <div v-if="cardToSell" class="sell-form">
                            <span>Prix:</span>
                            <input type="number" v-model="sellPrice" min="10" class="input-glass">
                            <button @click="postSale" class="btn-action">Vendre</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'vortex'" class="tab-content vortex-tab">
                <div class="vortex-container">
                    <div class="vortex-visual" :class="{active: vortexAnim}"></div>
                    
                    <div v-if="!vortexResult" class="vortex-input">
                        <p>Sacrifiez une carte au Néant...</p>
                        
                        <input v-model="vortexSearch" placeholder="Chercher une carte..." class="input-glass search-vortex">
                        
                        <select v-model="vortexSelectedId" class="input-glass vortex-select">
                            <option :value="null">-- Sélectionner --</option>
                            <option v-for="card in filteredInventory" :value="card.id" :key="card.id">
                                {{ card.name }} (x{{card.user_quantity}})
                            </option>
                        </select>

                        <button @click="launchVortex" class="btn-danger btn-vortex-action" :disabled="!vortexSelectedId">
                            ACTIVER LE VORTEX
                        </button>
                    </div>

                    <div v-else class="vortex-result">
                        <h2>Le Néant a répondu !</h2>
                        <div class="new-card-display">
                            <Card :card="vortexResult" />
                        </div>
                        <button @click="resetVortex" class="btn-action">Nouvel échange</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            activeTab: 'market',
            friends: [],
            market: [],
            inventory: [],
            friendCodeInput: '',
            cardToSell: null,
            sellPrice: 100,

            // Vortex
            vortexSelectedId: null,
            vortexSearch: '',
            vortexAnim: false,
            vortexResult: null
        }
    },
    computed: {
        // Sépare les offres des autres et les miennes
        othersListings() {
            return this.market.filter(m => m.seller_id != this.user.id);
        },
        myListings() {
            return this.market.filter(m => m.seller_id == this.user.id);
        },
        // Filtre la liste déroulante du Vortex
        filteredInventory() {
            if (!this.vortexSearch || this.vortexSearch.trim() === '') return this.inventory;
            const term = this.vortexSearch.toLowerCase().trim();
            return this.inventory.filter(c => c.name && c.name.toLowerCase().includes(term));
        }
    },
    mounted() { this.loadData(); },
    methods: {
        logout() { },
        //Méthode de chargement des données sociales
        async loadData() {
            try {
                const res = await axios.get('http://localhost/anime_game_card/api/routes.php?action=get_social_data', { withCredentials: true });
                this.friends = res.data.friends;
                this.market = res.data.market;
                this.inventory = res.data.inventory.filter(c => c.user_quantity > 0);
            } catch (e) { console.error("Erreur chargement social"); }
        },
        getAvatarUrl(avatar) {
            return avatar && avatar !== 'default_avatar.png' ? `./assets/img/avatars/${avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(this.user.username)}&background=00AEEF&color=fff`;
        },
        async addFriend() {
            if (!this.friendCodeInput) return alert("Veuillez entrer un code ami !");
            try {
                await axios.post('http://localhost/anime_game_card/api/routes.php?action=add_friend', { friend_code: this.friendCodeInput }, { withCredentials: true });
                alert("Ami ajouté avec succès !");
                this.friendCodeInput = '';
                this.loadData();
            } catch (e) {
                console.error(e);
                alert(e.response?.data?.message || "Erreur lors de l'ajout de l'ami");
            }
        },
        //Méthode de suppression d'un ami
        async removeFriend(friendId) {
            if (!confirm("Voulez-vous vraiment retirer cet ami ?")) return;
            try {
                await axios.post('http://localhost/anime_game_card/api/routes.php?action=remove_friend', { friend_id: friendId }, { withCredentials: true });
                this.loadData();
            } catch (e) {
                alert(e.response?.data?.message || "Erreur lors de la suppression");
            }
        },
        selectForSale(card) { this.cardToSell = card; },

        async postSale() {
            try {
                await axios.post('http://localhost/anime_game_card/api/routes.php?action=sell_card', { card_id: this.cardToSell.id, price: this.sellPrice }, { withCredentials: true });
                alert("Carte mise en vente !");
                this.loadData();
                this.cardToSell = null;
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
        },

        // Annuler une vente
        async cancelSale(listingId) {
            if (!confirm("Retirer cette offre ?")) return;
            try {
                await axios.post('http://localhost/anime_game_card/api/routes.php?action=cancel_sale', { listing_id: listingId }, { withCredentials: true });
                this.loadData(); // Recharger pour voir la carte revenir
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
        },
        //Méthode d'achat d'une carte
        async buyCard(offer) {
            if (this.user.gold < offer.price_gold) return alert("Pas assez d'or !");
            if (confirm(`Acheter ${offer.card_name} pour ${offer.price_gold} Or ?`)) {
                try {
                    await axios.post('http://localhost/anime_game_card/api/routes.php?action=buy_card', { listing_id: offer.id }, { withCredentials: true });
                    alert("Achat réussi !");
                    this.user.gold -= offer.price_gold;
                    localStorage.setItem('user', JSON.stringify(this.user));
                    this.loadData();
                } catch (e) { alert(e.response?.data?.message || "Erreur"); }
            }
        },
        //Méthode de lancement du vortex
        async launchVortex() {
            if (!confirm("Cette carte sera perdue à jamais. Continuer ?")) return;
            this.vortexAnim = true;
            try {
                setTimeout(async () => {
                    const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=wonder_trade', { card_id: this.vortexSelectedId }, { withCredentials: true });
                    this.vortexResult = res.data.new_card;
                    this.vortexAnim = false;
                    this.loadData();
                }, 2000);
            } catch (e) { alert("Le Vortex a échoué."); this.vortexAnim = false; }
        },
        //Méthode de réinitialisation du vortex
        resetVortex() {
            this.vortexResult = null;
            this.vortexSelectedId = null;
            this.vortexSearch = '';
        }
    }
}