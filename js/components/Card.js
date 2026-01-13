// Fichier de composant pour les cartes
export default {
    props: ['card', 'small'], // 'small' pour l'affichage compact dans la liste de deck
    template: `
    <div v-if="small" class="card-strip" :class="card.rarity.toLowerCase()">
        <div class="cost-bubble">{{ card.cost }}</div>
        <span class="card-name">{{ card.name }}</span>
        <span class="card-qty">x{{ card.quantity }}</span>
    </div>

    <div v-else class="anime-card" :class="[card.rarity.toLowerCase(), { 'grayscale': card.user_quantity == 0 }]">
        <div class="card-header">
            <div class="mana-crystal">{{ card.cost }}</div>
            <div class="card-name-top">{{ card.name }}</div>
        </div>

        <div class="card-image">
             <img :src="imagePath" alt="Card Art">
        </div>

        <div class="card-body">
            <div class="card-type">{{ card.type }}</div>
            <p class="card-desc">{{ card.description }}</p>
        </div>

        <div class="card-footer" v-if="card.type === 'Unit'">
            <div class="stat atk">⚔️ {{ card.attack }}</div>
            <div class="stat hp">🛡️ {{ card.hp }}</div>
        </div>

        <div class="owned-counter" v-if="card.user_quantity > 0">x{{ card.user_quantity }}</div>
    </div>
    `,
    computed: {
        imagePath() {
            const img = this.card.image_url || this.card.image;
            return img
                ? `./assets/img/cards/${img}`
                : 'https://via.placeholder.com/200x150?text=ART';
        }
    }
}