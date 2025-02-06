import PageSubscription from './PageSubscription.js';

class Listener {
  events = [];

  constructor() {
    this.initStates();
    this.addEventListener(PageSubscription.Type.Subscription);
    this.addEventListener(PageSubscription.Type.Favorite);
  }

  initStates() {
    const subscriptionButtons = document.querySelectorAll(`[data-page-subscription-${PageSubscription.Type.Subscription}-toggle]`);
    subscriptionButtons.forEach((button) => {
      this.initializeSubscription(button);
    });

    const favoriteButtons = document.querySelectorAll(`[data-page-subscription-${PageSubscription.Type.Favorite}-toggle]`);
    favoriteButtons.forEach((button) => {
      this.initializeFavorite(button);
    });
  }

  addEventListener(type) {
    const eventHandler = (event) => {
      const button = event.target.closest(`[data-page-subscription-${type}-toggle]`);
      if (button) {
        const active = PageSubscription.toggle(button, type, button.getAttribute('data-page-subscription-pid'));
        this.switch(button, active);
      }
    }
    document.addEventListener('click', eventHandler);
    this.events.push(eventHandler);
  }

  removeEventListeners() {
    this.events.forEach((event) => {
      document.removeEventListener('click', event);
    });
  }

  async initializeSubscription(subscriptionButton) {
    const active = await PageSubscription.check(PageSubscription.Type.Subscription, subscriptionButton);
    this.switch(subscriptionButton, active);
  }

  async initializeFavorite(favoriteButton) {
    const active = await PageSubscription.check(PageSubscription.Type.Favorite, favoriteButton);
    this.switch(favoriteButton, active);
  }

  switch(element, active) {
    if (active) {
      element.querySelector('[data-page-subscription-active]').classList.remove('hidden');
      element.querySelector('[data-page-subscription-inactive]').classList.add('hidden');
    } else {
      element.querySelector('[data-page-subscription-active]').classList.add('hidden');
      element.querySelector('[data-page-subscription-inactive]').classList.remove('hidden');
    }
  }
}

export default new Listener();
