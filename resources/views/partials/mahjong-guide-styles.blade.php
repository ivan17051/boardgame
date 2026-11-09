    .score-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.35rem;
      padding: 0.3rem;
      margin-bottom: 1rem;
      background: rgba(0, 97, 49, 0.08);
      border-radius: 0.85rem;
    }
    .score-tabs .score-tab {
      border: 0;
      border-radius: 0.65rem;
      background: transparent;
      color: #495057;
      font-weight: 600;
      padding: 0.65rem 0.5rem;
    }
    .score-tabs .score-tab.active {
      background: #fff;
      color: var(--score-brand-dark, #004d26);
      box-shadow: 0 2px 8px rgba(0, 60, 30, 0.1);
    }
    .guide-card h2 {
      color: var(--score-brand-dark, #004d26);
    }
    .guide-card h3 {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--score-brand-dark, #004d26);
      margin: 1.15rem 0 0.5rem;
    }
    .guide-card h3:first-of-type {
      margin-top: 0;
    }
    .guide-card p,
    .guide-card li {
      font-size: 0.875rem;
    }
    .guide-card ul,
    .guide-card ol {
      padding-left: 1.25rem;
      margin-bottom: 0.75rem;
    }
    .guide-table {
      width: 100%;
      margin-bottom: 0.75rem;
      font-size: 0.8rem;
    }
    .guide-table th,
    .guide-table td {
      padding: 0.45rem 0.5rem;
      vertical-align: middle;
    }
    .guide-table thead th {
      background: #f3f8f5;
      color: var(--score-brand-dark, #004d26);
    }
    .guide-note {
      border-left: 3px solid #ffc107;
      background: #fff8df;
      border-radius: 0.35rem;
      padding: 0.65rem 0.75rem;
      color: #664d03;
      font-size: 0.8rem;
    }
    .guide-meld-examples {
      display: grid;
      gap: 0.6rem;
      margin: 0.75rem 0 1rem;
    }
    .guide-meld {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.65rem;
      background: #f7faf8;
      border: 1px solid rgba(0, 97, 49, 0.1);
      border-radius: 0.75rem;
    }
    .guide-meld-label {
      flex: 0 0 5.2rem;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--score-brand-dark, #004d26);
    }
    .guide-tiles {
      display: flex;
      align-items: center;
      gap: 0.2rem;
      min-width: max-content;
    }
    .guide-tile {
      display: block;
      flex: 0 0 auto;
      width: 2.25rem;
      height: 3rem;
      object-fit: contain;
      box-sizing: border-box;
      padding: 0.15rem;
      background: linear-gradient(135deg, #fff 0%, #f5f2e9 100%);
      border: 1px solid #d5d1c5;
      border-bottom: 3px solid #c7c1b2;
      border-radius: 0.3rem;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    }
    .guide-example {
      margin: 0.75rem 0;
      padding: 0.75rem;
      background: #f7faf8;
      border: 1px solid rgba(0, 97, 49, 0.1);
      border-radius: 0.75rem;
    }
    .guide-example h4 {
      font-size: 0.88rem;
      font-weight: 700;
      color: var(--score-brand-dark, #004d26);
      margin: 0 0 0.35rem;
    }
    .guide-example .example-caption {
      font-size: 0.75rem;
      color: #6c757d;
      margin: 0 0 0.6rem;
    }
    .guide-winning-hand {
      display: flex;
      align-items: flex-start;
      gap: 0.55rem;
      overflow-x: auto;
      padding: 0.15rem 0 0.45rem;
      scrollbar-width: thin;
    }
    .guide-hand-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.25rem;
      flex: 0 0 auto;
    }
    .guide-hand-group .guide-tiles {
      gap: 0.12rem;
    }
    .guide-hand-group .guide-tile {
      width: 1.8rem;
      height: 2.5rem;
    }
    .guide-hand-group small {
      font-size: 0.62rem;
      color: #6c757d;
      white-space: nowrap;
    }
    .guide-example .example-score {
      border-top: 1px solid rgba(0, 97, 49, 0.1);
      padding-top: 0.5rem;
      margin-top: 0.45rem;
      font-size: 0.78rem;
    }
    .guide-card {
      background: #fff;
      border-radius: 1rem;
      border: 1px solid rgba(0, 97, 49, 0.12);
      box-shadow: 0 8px 32px rgba(0, 60, 30, 0.08);
      padding: 1rem;
      margin-bottom: 1rem;
    }
    .guide-card h2 {
      font-size: 1rem;
      font-weight: 700;
      margin: 0 0 0.75rem;
    }
