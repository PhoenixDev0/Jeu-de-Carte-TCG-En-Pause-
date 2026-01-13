// Fichier de service pour les appels API
const API_URL = 'http://localhost/anime_game_card/api/routes.php';

export default {
    async startTraining(deckId) {
        return axios.post(`${API_URL}?action=start_training`, { deck_id: deckId }, { withCredentials: true });
    },
    async getGameState(gameId) {
        return axios.get(`${API_URL}?action=get_game_state&id=${gameId}`, { withCredentials: true });
    }
};