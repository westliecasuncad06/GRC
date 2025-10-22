// ==========================================
// GRC User Manual Portal - Enhanced Script
// ==========================================

const links = Array.from(document.querySelectorAll('.nav-link[data-file]'));
const manualEl = document.getElementById('manual');
const searchInput = document.getElementById('search');
const downloadBtn = document.getElementById('download-pdf');
const toggleSidebarBtn = document.getElementById('toggle-sidebar');
const sidebar = document.getElementById('sidebar');
const currentManualEl = document.getElementById('current-manual');
const loadingEl = document.getElementById('loading');
const backToTopBtn = document.getElementById('back-to-top');
const tocToggle = document.getElementById('toc-toggle');
const tocSidebar = document.getElementById('toc-sidebar');
const tocClose = document.getElementById('toc-close');
const tocContent = document.getElementById('toc-content');
const manualCards = document.querySelectorAll('.manual-card');

let currentManualContent = '';

// ==========================================
// Load Manual Function
// ==========================================
async function loadManual(path, title) {
  // Show loading
  loadingEl.classList.add('active');
  manualEl.style.display = 'none';
  
  try {
    const res = await fetch(path);
    if (!res.ok) throw new Error('Failed to load');
    
    const md = await res.text();
    currentManualContent = md;
    
    // Render markdown
    manualEl.innerHTML = marked.parse(md, {
      mangle: false,
      headerIds: true,
      breaks: true
    });
    
    // Generate IDs for headings
    Array.from(manualEl.querySelectorAll('h1, h2, h3, h4')).forEach(h => {
      if (!h.id) {
        h.id = h.textContent.trim().toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-|-$/g, '');
      }
    });
    
    // Update breadcrumb
    currentManualEl.textContent = title;
    
    // Generate Table of Contents
    generateTOC();
    
    // Hide loading, show content
    loadingEl.classList.remove('active');
    manualEl.style.display = 'block';
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
  } catch (err) {
    loadingEl.classList.remove('active');
    manualEl.style.display = 'block';
    manualEl.innerHTML = `
      <div class="welcome-screen">
        <div class="welcome-icon" style="color: var(--danger);">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1>Unable to Load Manual</h1>
        <p>Could not load the manual file. If you're opening this from the file system (file://), your browser may block loading local files.</p>
        <p><strong>Solution:</strong> Serve the site via your webserver (XAMPP) at <code>http://localhost/GRC/manuals/</code></p>
        <p style="margin-top: 20px;">
          <button onclick="location.reload()" class="btn-primary" style="display: inline-flex;">
            <i class="fas fa-redo"></i>
            <span>Retry</span>
          </button>
        </p>
      </div>
    `;
  }
}

// ==========================================
// Generate Table of Contents
// ==========================================
function generateTOC() {
  const headings = Array.from(manualEl.querySelectorAll('h1, h2, h3'));
  
  if (headings.length === 0) {
    tocContent.innerHTML = '<p class="hint">No headings found</p>';
    return;
  }
  
  let tocHTML = '';
  headings.forEach(heading => {
    const level = heading.tagName.toLowerCase();
    const text = heading.textContent;
    const id = heading.id;
    
    if (id) {
      tocHTML += `<a href="#${id}" class="toc-${level}">${text}</a>`;
    }
  });
  
  tocContent.innerHTML = tocHTML;
  
  // Add click handlers for smooth scrolling
  tocContent.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = link.getAttribute('href').substring(1);
      const targetEl = document.getElementById(targetId);
      
      if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Close TOC on mobile
        if (window.innerWidth <= 1024) {
          tocSidebar.classList.remove('visible');
        }
      }
    });
  });
}

// ==========================================
// Navigation Link Handlers
// ==========================================
links.forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    
    // Update active state
    links.forEach(x => x.classList.remove('active'));
    a.classList.add('active');
    
    // Get file and title
    const file = a.dataset.file;
    const title = a.querySelector('.nav-title').textContent + ' Manual';
    
    // Load manual
    loadManual(file, title);
    
    // Hide sidebar on mobile
    if (window.innerWidth <= 768) {
      sidebar.classList.remove('visible');
    }
  });
});

// Manual cards click handlers
manualCards.forEach(card => {
  card.addEventListener('click', () => {
    const target = card.dataset.target;
    const link = document.querySelector(`.nav-link[data-role="${target}"]`);
    if (link) {
      link.click();
    }
  });
});

// ==========================================
// Search Functionality
// ==========================================
let searchMatches = [];
let currentMatchIndex = 0;

searchInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    performSearch();
  }
});

function performSearch() {
  const query = searchInput.value.trim().toLowerCase();
  
  if (!query) {
    alert('Please enter a search term');
    return;
  }
  
  // Clear previous highlights
  clearHighlights();
  
  // Search in manual
  const text = manualEl.textContent.toLowerCase();
  
  if (!text.includes(query)) {
    alert('No matches found in this manual.');
    return;
  }
  
  // Highlight all matches
  highlightMatches(query);
  
  // Scroll to first match
  if (searchMatches.length > 0) {
    scrollToMatch(0);
  }
}

function highlightMatches(query) {
  const walker = document.createTreeWalker(
    manualEl,
    NodeFilter.SHOW_TEXT,
    null,
    false
  );
  
  const nodesToReplace = [];
  let node;
  
  while (node = walker.nextNode()) {
    const text = node.nodeValue;
    const lowerText = text.toLowerCase();
    
    if (lowerText.includes(query)) {
      nodesToReplace.push(node);
    }
  }
  
  nodesToReplace.forEach(node => {
    const text = node.nodeValue;
    const lowerText = text.toLowerCase();
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    
    const span = document.createElement('span');
    span.innerHTML = text.replace(regex, '<mark class="highlight">$1</mark>');
    
    node.parentNode.replaceChild(span, node);
  });
  
  // Get all highlights
  searchMatches = Array.from(manualEl.querySelectorAll('.highlight'));
}

function clearHighlights() {
  const highlights = manualEl.querySelectorAll('.highlight');
  highlights.forEach(mark => {
    const text = mark.textContent;
    const textNode = document.createTextNode(text);
    mark.parentNode.replaceChild(textNode, mark);
  });
  searchMatches = [];
  currentMatchIndex = 0;
}

function scrollToMatch(index) {
  if (searchMatches.length === 0) return;
  
  const match = searchMatches[index];
  match.scrollIntoView({ behavior: 'smooth', block: 'center' });
  
  // Add temporary emphasis
  match.style.background = '#ffeb3b';
  setTimeout(() => {
    match.style.background = 'yellow';
  }, 2000);
}

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// ==========================================
// Sidebar Toggle (Mobile)
// ==========================================
if (toggleSidebarBtn) {
  toggleSidebarBtn.addEventListener('click', () => {
    sidebar.classList.toggle('visible');
  });
}

// ==========================================
// Table of Contents Toggle
// ==========================================
if (tocToggle) {
  tocToggle.addEventListener('click', () => {
    tocSidebar.classList.add('visible');
  });
}

if (tocClose) {
  tocClose.addEventListener('click', () => {
    tocSidebar.classList.remove('visible');
  });
}

// ==========================================
// Download PDF (Print)
// ==========================================
if (downloadBtn) {
  downloadBtn.addEventListener('click', () => {
    window.print();
  });
}

// ==========================================
// Back to Top Button
// ==========================================
window.addEventListener('scroll', () => {
  if (window.pageYOffset > 300) {
    backToTopBtn.classList.add('visible');
  } else {
    backToTopBtn.classList.remove('visible');
  }
});

backToTopBtn.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ==========================================
// Quick Links
// ==========================================
const printViewBtn = document.getElementById('print-view');
const fullscreenBtn = document.getElementById('fullscreen');

if (printViewBtn) {
  printViewBtn.addEventListener('click', (e) => {
    e.preventDefault();
    window.print();
  });
}

if (fullscreenBtn) {
  fullscreenBtn.addEventListener('click', (e) => {
    e.preventDefault();
    
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(err => {
        alert('Error attempting to enable fullscreen');
      });
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  });
}

// ==========================================
// Close sidebar when clicking outside (mobile)
// ==========================================
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 768) {
    if (!sidebar.contains(e.target) && !toggleSidebarBtn.contains(e.target)) {
      sidebar.classList.remove('visible');
    }
  }
});

// ==========================================
// Close TOC when clicking outside
// ==========================================
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 1024) {
    if (!tocSidebar.contains(e.target) && !tocToggle.contains(e.target)) {
      tocSidebar.classList.remove('visible');
    }
  }
});

// ==========================================
// Keyboard Shortcuts
// ==========================================
document.addEventListener('keydown', (e) => {
  // Ctrl/Cmd + K for search focus
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    searchInput.focus();
  }
  
  // Escape to close sidebars
  if (e.key === 'Escape') {
    sidebar.classList.remove('visible');
    tocSidebar.classList.remove('visible');
  }
  
  // Ctrl/Cmd + P for print
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    e.preventDefault();
    window.print();
  }
});

// ==========================================
// Initial Load
// ==========================================
// Auto-load first manual on page load
if (links.length > 0) {
  const firstLink = links[0];
  const file = firstLink.dataset.file;
  const title = firstLink.querySelector('.nav-title').textContent + ' Manual';
  loadManual(file, title);
}

// ==========================================
// Handle anchor links in URL
// ==========================================
window.addEventListener('load', () => {
  if (window.location.hash) {
    setTimeout(() => {
      const targetEl = document.querySelector(window.location.hash);
      if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth' });
      }
    }, 500);
  }
});

// ==========================================
// Console Welcome Message
// ==========================================
console.log('%c GRC User Manuals Portal ', 'background: #DC143C; color: white; font-size: 16px; padding: 10px;');
console.log('%c Version 1.0 - October 2025 ', 'color: #6c757d; font-size: 12px;');
console.log('%c Keyboard Shortcuts: ', 'font-weight: bold; margin-top: 10px;');
console.log('  Ctrl/Cmd + K: Focus search');
console.log('  Ctrl/Cmd + P: Print/Download PDF');
console.log('  Esc: Close sidebars');
