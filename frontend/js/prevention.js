const searchForm = document.querySelector('.search-form');
const searchInput = document.querySelector('#prevention-search');
const tipCards = [...document.querySelectorAll('.tip-card')];
const searchResults = document.querySelector('#search-results');
const searchEmpty = document.querySelector('#search-empty');

if (searchForm && searchInput && tipCards.length && searchResults && searchEmpty) {
    const normalize = (value) => value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('fr');

    const filterTips = () => {
        const query = normalize(searchInput.value.trim());
        const terms = query.split(/\s+/).filter(Boolean);
        let visibleCount = 0;

        tipCards.forEach((card) => {
            const searchableText = normalize(`${card.dataset.searchTerms || ''} ${card.textContent}`);
            const matches = terms.every((term) => searchableText.includes(term));

            card.hidden = !matches;
            visibleCount += Number(matches);
        });

        searchEmpty.hidden = visibleCount !== 0;
        searchResults.textContent = query
            ? `${visibleCount} conseil${visibleCount === 1 ? '' : 's'} trouvé${visibleCount === 1 ? '' : 's'}.`
            : '';
    };

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        filterTips();
    });

    searchInput.addEventListener('input', filterTips);
}
