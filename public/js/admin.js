function loadStats() {
    for (const id of ['activeCount', 'respondersCount', 'resolvedCount']) {
        document.getElementById(id).textContent = '0';
    }
}

function switchTab(tabName) {
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
    });

    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = content.id === `${tabName}Tab` ? 'block' : 'none';
    });
}


function init() {
    loadStats();

    // Tab navigation
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

}

init();
