// Fichier principal de l'application

// Imports avec versioning pour forcer le rafraîchissement
import LoginView from './views/LoginView.js?v=SOCIAL_V1';
import HubView from './views/HubView.js?v=SOCIAL_V1';
import CollectionView from './views/CollectionView.js?v=SOCIAL_V1';
import ShopView from './views/ShopView.js?v=SOCIAL_V1';
import ProfileView from './views/ProfileView.js?v=SOCIAL_V1';
import SocialView from './views/SocialView.js?v=SOCIAL_V1';
import LeaderboardView from './views/LeaderboardView.js?v=SOCIAL_V1';
import BattleView from './views/BattleView.js?v=SOCIAL_V1';
import GameBoard from './views/GameBoard.js?v=GAME_V1';

const { createApp } = Vue;

const app = createApp({
    data() {
        return {
            currentView: 'LoginView',
            loading: false,
            user: null,
            currentProps: {}
        }
    },
    components: {
        LoginView,
        HubView,
        BattleView,
        CollectionView,
        ShopView,
        ProfileView,
        SocialView,
        LeaderboardView,
        GameBoard
    },
    methods: {
        // Méthode de changement de vue
        changeView(viewName, params = {}) {
            this.loading = true;

            this.currentProps = params;

            setTimeout(() => {
                this.currentView = viewName;
                this.loading = false;
            }, 300);
        },
        //Méthode de mise à jour de l'utilisateur
        updateUser(userData) {
            this.user = userData;
            localStorage.setItem('user', JSON.stringify(userData));
        }
    },
    mounted() {
        const savedUser = localStorage.getItem('user');
        if (savedUser) {
            try {
                this.user = JSON.parse(savedUser);
            } catch (e) {
                localStorage.removeItem('user');
            }
        }
        console.log("Anime Game Card V2 - Ready");
    }
});

app.mount('#app');