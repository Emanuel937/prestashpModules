const blockedWords = ["xxx", "porn", "sexy", "adult", "nude", "erotic"];

function checkSearch(query) {
    return blockedWords.some(word => query.toLowerCase().includes(word));
}

browser.webRequest.onBeforeRequest.addListener(
    (details) => {
        const url = new URL(details.url);
        const searchParams = new URLSearchParams(url.search);
        const query = searchParams.get("q") || "";

        if (checkSearch(query)) {
            return { redirectUrl: "data:text/html,<h1>🚫 Recherche bloquée !</h1>" };
        }
    },
    { urls: [
        "*://www.google.com/search*",
        "*://www.bing.com/search*",
        "*://search.yahoo.com/search*",
        "*://duckduckgo.com/*"
    ] },
    ["blocking"]
);

browser.management.onUninstall.addListener(async (info) => {
    let password = prompt("Entrez le mot de passe pour supprimer l'extension :");
    if (password !== "MonMotDePasseSecurise") {
        alert("Mot de passe incorrect ! Suppression annulée.");
    }
});
