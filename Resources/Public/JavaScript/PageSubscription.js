class PageSubscription {
  Type = {
    Subscription: 'subscription',
    Favorite: 'favorite',
  };

  async check(type, pid, elementId) {
    return await this.action('check', type, pid, elementId);
  }

  async toggle(element, type, pid, elementId) {
    element.disabled = true;
    const active = await this.action('toggle', type, pid, elementId);
    element.disabled = false;
    return active;
  }

  async action(action, type, pid, elementId) {
    const url = new URL(window.location.href);
    url.searchParams.set('type', '1728035511');

    const body = new URLSearchParams();
    body.set('action', action);
    body.set('type', type);
    if (pid) {
      body.set('pid', pid);
    }
    if (elementId) {
      body.set('elementId', elementId);
    }
    try {
      const response = await fetch(url.toString(), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: body.toString(),
      });
      const data = await response.json();
      document.dispatchEvent(new CustomEvent('page-subscription-action', {detail: data}));
      return data;
    } catch (error) {
      console.error('Error:', error);
      return false;
    }
  }
}

const pageSubscription = new PageSubscription();
export default pageSubscription;
window.PageSubscription = pageSubscription;
