  (function () {
    const tabs = Array.from(document.querySelectorAll('.score-tabs [role="tab"]'));
    const panels = tabs.map(function (tab) {
      return document.getElementById(tab.getAttribute('aria-controls'));
    });

    function activateTab(index, moveFocus) {
      tabs.forEach(function (tab, tabIndex) {
        const selected = tabIndex === index;
        tab.classList.toggle('active', selected);
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
        panels[tabIndex].hidden = !selected;
        panels[tabIndex].classList.toggle('active', selected);
      });

      if (moveFocus) tabs[index].focus();
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        activateTab(index, false);
      });
      tab.addEventListener('keydown', function (event) {
        let nextIndex = index;
        if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
        else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
        else if (event.key === 'Home') nextIndex = 0;
        else if (event.key === 'End') nextIndex = tabs.length - 1;
        else return;
        event.preventDefault();
        activateTab(nextIndex, true);
      });
    });
  })();
