// Utility to automatically click "Follow" buttons while scrolling.
// Adds a random pause of up to 8 seconds between clicks.
// Usage: clickFollowButtonsWithScroll(20, 1000);

function clickFollowButtonsWithScroll(maxClicks, baseDelay) {
  const containerSelector = '#tux-portal-container > div > div:nth-child(2) > div > div > div.css-17s26nl-ModalContentContainer.e1wuf0b31 > div > div > section > div > div.css-wq5jjc-DivUserListContainer.eorzdsw0';
  const container = document.querySelector(containerSelector);

  if (!container) {
    console.error('Le conteneur pour les boutons n\'a pas été trouvé.');
    return;
  }

  let clickCount = 0;
  let isStopped = false;

  const getButtons = () => Array.from(document.querySelectorAll('button[data-e2e="follow-button"]')).filter(btn => /follow/i.test(btn.innerText));

  function clickButton() {
    if (isStopped || clickCount >= maxClicks) {
      console.log('Clics terminés.');
      return;
    }

    const buttons = getButtons();
    const button = buttons[clickCount % buttons.length];

    if (button) {
      try {
        button.click();
        clickCount++;
        if (window.chrome && chrome.runtime) {
          chrome.runtime.sendMessage({ type: 'click-update', count: clickCount });
          chrome.storage.local.set({ clickCount });
        }
      } catch (error) {
        console.error('Erreur lors du clic :', error);
      }
    } else {
      console.log('Aucun bouton disponible.');
    }

    if (clickCount % 8 === 0) {
      container.scrollBy({ top: 550, behavior: 'smooth' });
    }

    const randomPause = Math.random() * 8000; // 0-8000ms
    setTimeout(clickButton, baseDelay + randomPause);
  }

  clickButton();

  window.stopClicking = () => {
    isStopped = true;
  };
}
