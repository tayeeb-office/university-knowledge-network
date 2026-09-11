document.addEventListener("DOMContentLoaded", function () {
  var navMenu = document.getElementById("navMenu");
  var navLinks = navMenu ? navMenu.querySelectorAll(".nav-link") : [];

  // Close the mobile menu after tapping a nav link.
  navLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      if (navMenu.classList.contains("show")) {
        var bsCollapse = bootstrap.Collapse.getOrCreateInstance(navMenu);
        bsCollapse.hide();
      }
    });
  });

  // Highlight the current section's nav link while scrolling.
  var sections = Array.prototype.map.call(navLinks, function (link) {
    var id = link.getAttribute("href");
    return id && id.charAt(0) === "#" ? document.querySelector(id) : null;
  });

  function setActiveLink() {
    var scrollPos = window.scrollY + 96;
    var activeIndex = -1;

    sections.forEach(function (section, index) {
      if (section && section.offsetTop <= scrollPos) {
        activeIndex = index;
      }
    });

    navLinks.forEach(function (link, index) {
      link.classList.toggle("active", index === activeIndex);
    });
  }

  window.addEventListener("scroll", setActiveLink, { passive: true });
  setActiveLink();

  // Password visibility toggle (Login page).
  var pwToggle = document.getElementById("togglePassword");
  var pwInput = document.getElementById("loginPassword");

  if (pwToggle && pwInput) {
    pwToggle.addEventListener("click", function () {
      var showing = pwInput.type === "password";
      pwInput.type = showing ? "text" : "password";
      pwToggle.classList.toggle("pw-visible", showing);
      var label = showing ? "Hide password" : "Show password";
      pwToggle.setAttribute("aria-label", label);
      pwToggle.setAttribute("title", label);
    });
  }

  // Login form validation (frontend only — no submission, no real auth).
  var loginForm = document.getElementById("loginForm");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      loginForm.classList.add("was-validated");
    });
  }

  // Password visibility toggles (Register page — Password + Confirm Password).
  var pwToggleButtons = document.querySelectorAll("[data-toggle-for]");

  pwToggleButtons.forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute("data-toggle-for"));
    if (!input) return;

    btn.addEventListener("click", function () {
      var showing = input.type === "password";
      input.type = showing ? "text" : "password";
      btn.classList.toggle("pw-visible", showing);
      var label = showing ? "Hide password" : "Show password";
      btn.setAttribute("aria-label", label);
      btn.setAttribute("title", label);
    });
  });

  // Register page: live password strength hint + bio character counter + validation.
  var registerForm = document.getElementById("registerForm");

  if (registerForm) {
    var regPassword = document.getElementById("regPassword");
    var regConfirm = document.getElementById("regConfirm");
    var pwHint = document.getElementById("pwHint");
    var pwGood = document.getElementById("pwGood");
    var regBio = document.getElementById("regBio");
    var bioCount = document.getElementById("bioCount");
    var formError = document.getElementById("registerFormError");

    function isPasswordStrong(value) {
      return value.length >= 8 && /[0-9]/.test(value);
    }

    function updatePasswordHint() {
      var strong = isPasswordStrong(regPassword.value);
      pwHint.classList.toggle("d-none", regPassword.value.length === 0 || strong);
      pwGood.classList.toggle("d-none", !strong);
    }

    function updateConfirmMatch() {
      if (regConfirm.value && regConfirm.value !== regPassword.value) {
        regConfirm.setCustomValidity("Passwords do not match.");
      } else {
        regConfirm.setCustomValidity("");
      }
    }

    regPassword.addEventListener("input", function () {
      updatePasswordHint();
      updateConfirmMatch();
    });

    regConfirm.addEventListener("input", updateConfirmMatch);

    if (regBio && bioCount) {
      regBio.addEventListener("input", function () {
        bioCount.textContent = regBio.value.length + " / 300";
      });
    }

    // Live per-field validation feedback once a field has been visited.
    var liveFields = registerForm.querySelectorAll(".form-control, .form-select");
    liveFields.forEach(function (field) {
      field.addEventListener("blur", function () {
        field.classList.add("auth-touched");
        if (field.classList.contains("auth-touched")) {
          field.classList.toggle("is-valid", field.checkValidity());
          field.classList.toggle("is-invalid", !field.checkValidity());
        }
      });
      field.addEventListener("input", function () {
        if (field.classList.contains("auth-touched")) {
          field.classList.toggle("is-valid", field.checkValidity());
          field.classList.toggle("is-invalid", !field.checkValidity());
        }
      });
    });

    registerForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      updateConfirmMatch();

      if (!registerForm.checkValidity()) {
        registerForm.classList.add("was-validated");
        formError.textContent = "Please complete the required fields above.";
        formError.classList.remove("d-none");
        var firstInvalid = registerForm.querySelector(":invalid");
        if (firstInvalid) firstInvalid.focus();
        return;
      }

      formError.classList.add("d-none");
      registerForm.classList.add("was-validated");
    });
  }

  // Forgot Password form validation (frontend only — no email is actually sent).
  var forgotForm = document.getElementById("forgotForm");

  if (forgotForm) {
    var forgotEmail = document.getElementById("forgotEmail");
    var resetSuccess = document.getElementById("resetSuccess");
    var resetSentTo = document.getElementById("resetSentTo");
    var useDifferentEmail = document.getElementById("useDifferentEmail");

    forgotForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (!forgotForm.checkValidity()) {
        forgotForm.classList.add("was-validated");
        forgotEmail.focus();
        return;
      }

      resetSentTo.textContent = forgotEmail.value.trim();
      forgotForm.classList.add("d-none");
      resetSuccess.classList.remove("d-none");
    });

    if (useDifferentEmail) {
      useDifferentEmail.addEventListener("click", function () {
        forgotForm.reset();
        forgotForm.classList.remove("was-validated");
        resetSuccess.classList.add("d-none");
        forgotForm.classList.remove("d-none");
        forgotEmail.focus();
      });
    }
  }

  // ------------------------------------------------------------------------
  // Dashboard page
  // (Notification + profile dropdowns are handled by Bootstrap's built-in
  // dropdown component via data-bs-toggle="dropdown" — no extra JS needed.)
  // ------------------------------------------------------------------------

  // Sidebar navigation active state.
  var dashNavLinks = document.querySelectorAll(".dash-nav-link");
  dashNavLinks.forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      dashNavLinks.forEach(function (l) {
        l.classList.remove("active");
      });
      link.classList.add("active");
    });
  });

  // Community feed filter pills.
  var filterPills = document.querySelectorAll(".filter-pill");
  filterPills.forEach(function (pill) {
    pill.addEventListener("click", function () {
      filterPills.forEach(function (p) {
        p.classList.remove("active");
      });
      pill.classList.add("active");
    });
  });

  // Feed sort cycle button (Latest / Most Upvoted / Most Discussed).
  var sortToggle = document.getElementById("sortToggle");
  if (sortToggle) {
    var sortOptions = ["Latest", "Most Upvoted", "Most Discussed"];
    var sortIndex = 0;
    var sortLabel = sortToggle.querySelector(".sort-label");
    sortToggle.addEventListener("click", function () {
      sortIndex = (sortIndex + 1) % sortOptions.length;
      if (sortLabel) sortLabel.textContent = sortOptions[sortIndex];
    });
  }

  // Composer action buttons focus the share textarea.
  var composerTextarea = document.getElementById("composerText");
  var composerButtons = document.querySelectorAll(".composer-action-btn");
  composerButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (composerTextarea) composerTextarea.focus();
    });
  });

  // Feed post upvote / downvote / save — visual state only, no backend.
  var feedPosts = document.querySelectorAll(".feed-post");
  feedPosts.forEach(function (post) {
    var upBtn = post.querySelector(".js-upvote");
    var downBtn = post.querySelector(".js-downvote");
    var saveBtn = post.querySelector(".js-save");
    var scoreEl = post.querySelector(".js-vote-score");
    var baseScore = scoreEl ? parseInt(scoreEl.textContent, 10) || 0 : 0;
    var voteState = 0; // 1 = upvoted, -1 = downvoted, 0 = neutral

    function renderVote() {
      if (scoreEl) scoreEl.textContent = baseScore + voteState;
      if (upBtn) {
        upBtn.classList.toggle("is-up-active", voteState === 1);
        upBtn.setAttribute("aria-pressed", voteState === 1);
      }
      if (downBtn) {
        downBtn.classList.toggle("is-down-active", voteState === -1);
        downBtn.setAttribute("aria-pressed", voteState === -1);
      }
    }

    if (upBtn) {
      upBtn.addEventListener("click", function () {
        voteState = voteState === 1 ? 0 : 1;
        renderVote();
      });
    }
    if (downBtn) {
      downBtn.addEventListener("click", function () {
        voteState = voteState === -1 ? 0 : -1;
        renderVote();
      });
    }
    if (saveBtn) {
      saveBtn.addEventListener("click", function () {
        var saved = saveBtn.classList.toggle("is-save-active");
        saveBtn.setAttribute("aria-pressed", saved);
        var label = saveBtn.querySelector(".js-save-label");
        if (label) label.textContent = saved ? "Saved" : "Save";
      });
    }
  });

  // ------------------------------------------------------------------------
  // Student Profile page
  // ------------------------------------------------------------------------

  // Edit Profile button — visual editing-state toggle only (no real form).
  var editProfileBtn = document.getElementById("editProfileBtn");
  if (editProfileBtn) {
    editProfileBtn.addEventListener("click", function () {
      var editing = editProfileBtn.classList.toggle("is-editing");
      editProfileBtn.setAttribute("aria-pressed", editing);
      editProfileBtn.textContent = editing ? "Done Editing" : "Edit Profile";
    });
  }

  // Share Profile button — copies the page link and shows a temporary confirmation.
  var shareProfileBtn = document.getElementById("shareProfileBtn");
  if (shareProfileBtn) {
    var shareLabel = shareProfileBtn.querySelector(".js-share-label");
    var shareTimer = null;
    shareProfileBtn.addEventListener("click", function () {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(window.location.href).catch(function () {});
      }
      if (shareLabel) shareLabel.textContent = "Link copied";
      clearTimeout(shareTimer);
      shareTimer = setTimeout(function () {
        if (shareLabel) shareLabel.textContent = "Share Profile";
      }, 1800);
    });
  }

  // Profile tabs — active state only, all sections stay on one page.
  var profileTabs = document.querySelectorAll(".profile-tab");
  profileTabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      profileTabs.forEach(function (t) {
        t.classList.remove("active");
        t.setAttribute("aria-pressed", "false");
      });
      tab.classList.add("active");
      tab.setAttribute("aria-pressed", "true");
    });
  });

  // Availability — select a day to highlight it.
  var availabilityBtns = document.querySelectorAll(".availability-btn");
  availabilityBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      availabilityBtns.forEach(function (b) {
        b.classList.remove("is-selected");
        b.setAttribute("aria-pressed", "false");
      });
      btn.classList.add("is-selected");
      btn.setAttribute("aria-pressed", "true");
    });
  });

  // ------------------------------------------------------------------------
  // Skills page
  // ------------------------------------------------------------------------

  var trendingGrid = document.getElementById("trendingGrid");

  if (trendingGrid) {
    var SORTS = ["Most Popular", "Recently Added", "Highest Rated"];

    var SKILLS = [
      { slug: "javascript", name: "JavaScript", category: "Programming", learners: 124, mentors: 35 },
      { slug: "sql", name: "SQL", category: "Database", learners: 98, mentors: 28 },
      { slug: "ui-ux-design", name: "UI/UX Design", category: "Design", learners: 75, mentors: 20 },
      { slug: "python", name: "Python", category: "Programming", learners: 110, mentors: 30 },
      { slug: "public-speaking", name: "Public Speaking", category: "Communication", learners: 64, mentors: 18 },
      { slug: "business-analytics", name: "Business Analytics", category: "Business", learners: 58, mentors: 14 },
      { slug: "research-methods", name: "Research Methods", category: "Research", learners: 41, mentors: 11 },
      { slug: "figma", name: "Figma", category: "Design", learners: 52, mentors: 16 }
    ];

    var DETAILS = {
      javascript: { category: "Programming", updated: "2 days ago", description: "Frontend programming language used for building interactive web applications.", sessions: "210", rating: "4.8" },
      sql: { category: "Database", updated: "yesterday", description: "Query language for reading, joining and shaping data held in relational databases.", sessions: "164", rating: "4.7" },
      "ui-ux-design": { category: "Design", updated: "4 days ago", description: "Planning and designing interfaces students can actually use — research, wireframes and visual detail.", sessions: "118", rating: "4.9" },
      python: { category: "Programming", updated: "today", description: "General-purpose language widely used across coursework in scripting, data work and automation.", sessions: "186", rating: "4.8" },
      "public-speaking": { category: "Communication", updated: "3 days ago", description: "Structuring and delivering talks, vivas and presentations with confidence in front of a class.", sessions: "92", rating: "4.6" },
      "business-analytics": { category: "Business", updated: "5 days ago", description: "Turning coursework datasets into recommendations using spreadsheets, dashboards and clear write-ups.", sessions: "76", rating: "4.7" },
      "research-methods": { category: "Research", updated: "a week ago", description: "Designing a study, choosing a sampling approach and reporting findings for a thesis or term paper.", sessions: "54", rating: "4.8" },
      figma: { category: "Design", updated: "yesterday", description: "Building and sharing interface mockups, components and prototypes for team projects.", sessions: "88", rating: "4.9" }
    };

    var MENTORS_BY_SKILL = {
      javascript: [
        { name: "Nafis Rahman", initials: "NR", dept: "CSE · 4th Year", level: "Advanced", rating: "4.9", points: "520", sessions: "142", avatarBg: "#17324D" },
        { name: "Ayesha Siddika", initials: "AS", dept: "CSE · 4th Year", level: "Advanced", rating: "4.8", points: "465", sessions: "97", avatarBg: "#2A7F78" },
        { name: "Sakib Hasan", initials: "SH", dept: "CSE · 3rd Year", level: "Intermediate", rating: "4.7", points: "420", sessions: "78", avatarBg: "#17324D" },
        { name: "Rifat Chowdhury", initials: "RC", dept: "SWE · 3rd Year", level: "Intermediate", rating: "4.6", points: "310", sessions: "54", avatarBg: "#2A7F78" }
      ]
    };

    var DEFAULT_MENTORS = [
      { name: "Tanvir Ahmed", initials: "TA", dept: "EEE · 3rd Year", level: "Advanced", rating: "4.8", points: "465", sessions: "104", avatarBg: "#17324D" },
      { name: "Nusrat Jahan", initials: "NJ", dept: "BBA · 2nd Year", level: "Intermediate", rating: "4.7", points: "295", sessions: "61", avatarBg: "#2A7F78" },
      { name: "Farhan Kabir", initials: "FK", dept: "CSE · 4th Year", level: "Advanced", rating: "4.9", points: "410", sessions: "88", avatarBg: "#17324D" },
      { name: "Maisha Islam", initials: "MI", dept: "Statistics · 3rd Year", level: "Intermediate", rating: "4.6", points: "268", sessions: "47", avatarBg: "#2A7F78" }
    ];

    var skillsState = {
      filter: "All Skills",
      query: "",
      sortIndex: 0,
      selectedSkill: "javascript",
      requested: {}
    };

    function levelClass(level) {
      if (level === "Advanced") return "is-advanced";
      if (level === "Intermediate") return "is-intermediate";
      return "is-beginner";
    }

    function visibleSkills() {
      var q = skillsState.query;
      var list = SKILLS.filter(function (s) {
        var matchesFilter = skillsState.filter === "All Skills" || s.category === skillsState.filter;
        var matchesQuery = !q || s.name.toLowerCase().indexOf(q) !== -1 || s.category.toLowerCase().indexOf(q) !== -1;
        return matchesFilter && matchesQuery;
      });
      var sort = SORTS[skillsState.sortIndex];
      if (sort === "Most Popular") {
        list = list.slice().sort(function (a, b) { return b.learners - a.learners; });
      } else if (sort === "Highest Rated") {
        list = list.slice().sort(function (a, b) { return b.mentors - a.mentors; });
      }
      return list;
    }

    function trendingCardHtml(s) {
      var selected = s.slug === skillsState.selectedSkill;
      return (
        '<div class="col-sm-6 col-lg-3">' +
          '<div class="mentor-mini-card d-flex flex-column h-100"' + (selected ? ' style="border-color:#17324D;box-shadow:0 8px 22px rgba(23,50,77,0.10);"' : "") + '>' +
            '<div class="d-flex align-items-start justify-content-between gap-2">' +
              '<div class="min-w-0">' +
                '<div class="heading-font fw-semibold" style="font-size:17px;color:#17324d;line-height:1.3;">' + s.name + '</div>' +
                '<div class="mt-1" style="font-size:14px;color:#64748b;">' + s.category + '</div>' +
              '</div>' +
              (selected ? '<span class="dash-nav-badge flex-shrink-0" style="background:#17324D;color:#F2B84B;border-color:#17324D;">Viewing</span>' : "") +
            '</div>' +
            '<div class="d-flex gap-4 mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">' +
              '<div><div class="heading-font fw-semibold" style="font-size:18px;color:#17324d;">' + s.learners + '</div><div style="font-size:13px;color:#64748b;">Students</div></div>' +
              '<div><div class="heading-font fw-semibold" style="font-size:18px;color:#17324d;">' + s.mentors + '</div><div style="font-size:13px;color:#64748b;">Mentors</div></div>' +
            '</div>' +
            '<button type="button" class="btn btn-outline-navy-card w-100 mt-3 js-view-skill" data-skill="' + s.slug + '">View Skill</button>' +
          '</div>' +
        '</div>'
      );
    }

    function renderTrending() {
      var list = visibleSkills();
      var resultCount = document.getElementById("resultCount");
      var sortSummary = document.getElementById("sortSummary");
      if (resultCount) resultCount.textContent = list.length + " skills in the " + skillsState.filter + " view";
      if (sortSummary) sortSummary.textContent = "Sorted by " + SORTS[skillsState.sortIndex];
      trendingGrid.innerHTML = list.length
        ? list.map(trendingCardHtml).join("")
        : '<p class="mb-0" style="color:#64748b;">No skills match your search.</p>';
    }

    function mentorCardHtml(m, slug) {
      var key = slug + "|" + m.name;
      var sent = !!skillsState.requested[key];
      return (
        '<div class="col-sm-6 col-lg-3">' +
          '<div class="mentor-mini-card d-flex flex-column h-100">' +
            '<div class="d-flex align-items-center gap-3">' +
              '<span class="mentor-avatar" style="width:48px;height:48px;font-size:15px;background:' + m.avatarBg + ';">' + m.initials + '</span>' +
              '<div class="min-w-0">' +
                '<div class="heading-font fw-semibold" style="font-size:16px;color:#17324d;line-height:1.3;">' + m.name + '</div>' +
                '<div style="font-size:14px;color:#64748b;">' + m.dept + '</div>' +
              '</div>' +
            '</div>' +
            '<div class="d-flex flex-wrap align-items-center gap-2 mt-3">' +
              '<span class="level-badge ' + levelClass(m.level) + '">' + m.level + '</span>' +
              '<span style="font-size:14px;color:#17212b;"><span style="color:#f2b84b;">★</span> <strong style="font-weight:700;">' + m.rating + '</strong></span>' +
            '</div>' +
            '<div class="d-flex gap-4 mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">' +
              '<div><div class="heading-font fw-semibold" style="font-size:17px;color:#17324d;">' + m.points + '</div><div style="font-size:13px;color:#64748b;">Mentor points</div></div>' +
              '<div><div class="heading-font fw-semibold" style="font-size:17px;color:#17324d;">' + m.sessions + '</div><div style="font-size:13px;color:#64748b;">Sessions</div></div>' +
            '</div>' +
            '<div class="d-flex flex-wrap gap-2 mt-3">' +
              '<a href="profile.html" class="btn btn-outline-navy-card btn-sm flex-fill">View Profile</a>' +
              '<button type="button" class="request-btn js-request' + (sent ? " is-sent" : "") + '" data-key="' + key + '">' + (sent ? "Request Sent" : "Request Session") + '</button>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    }

    function renderMentors(slug) {
      var mentorsGrid = document.getElementById("mentorsGrid");
      if (!mentorsGrid) return;
      var list = MENTORS_BY_SKILL[slug] || DEFAULT_MENTORS;
      mentorsGrid.innerHTML = list.map(function (m) { return mentorCardHtml(m, slug); }).join("");
    }

    function selectSkill(slug) {
      var skill = SKILLS.filter(function (s) { return s.slug === slug; })[0];
      var detail = DETAILS[slug];
      if (!skill || !detail) return;
      skillsState.selectedSkill = slug;

      var setText = function (id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
      };

      setText("detailName", skill.name);
      setText("detailCategory", detail.category);
      setText("detailUpdated", "Updated " + detail.updated);
      setText("detailDescription", detail.description);
      setText("detailStudents", skill.learners);
      setText("detailMentors", skill.mentors);
      setText("detailSessions", detail.sessions);
      setText("detailRating", detail.rating);
      setText("mentorsSkillName", skill.name);
      setText("mentorsCountLink", skill.mentors);

      renderMentors(slug);
      renderTrending();
    }

    // Search input.
    var skillSearchInput = document.getElementById("skillSearchInput");
    if (skillSearchInput) {
      skillSearchInput.addEventListener("input", function () {
        skillsState.query = skillSearchInput.value.trim().toLowerCase();
        renderTrending();
      });
    }

    // Filter pills.
    var skillFilterButtons = document.querySelectorAll("#skillFilters .filter-pill");
    skillFilterButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        skillsState.filter = btn.getAttribute("data-filter");
        skillFilterButtons.forEach(function (b) { b.classList.remove("active"); });
        btn.classList.add("active");
        renderTrending();
      });
    });

    // Category cards.
    var categoryCards = document.querySelectorAll(".js-category-card");
    categoryCards.forEach(function (card) {
      card.addEventListener("click", function (e) {
        e.preventDefault();
        var cat = card.getAttribute("data-category");
        skillsState.filter = cat;
        categoryCards.forEach(function (c) { c.classList.remove("is-selected"); });
        card.classList.add("is-selected");
        skillFilterButtons.forEach(function (b) {
          b.classList.toggle("active", b.getAttribute("data-filter") === cat);
        });
        renderTrending();
        var trendingSection = document.getElementById("trending");
        if (trendingSection) trendingSection.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });

    // Sort cycle button.
    var skillSortBtn = document.getElementById("skillSortBtn");
    if (skillSortBtn) {
      var skillSortLabel = skillSortBtn.querySelector(".sort-label");
      skillSortBtn.addEventListener("click", function () {
        skillsState.sortIndex = (skillsState.sortIndex + 1) % SORTS.length;
        if (skillSortLabel) skillSortLabel.textContent = SORTS[skillsState.sortIndex];
        renderTrending();
      });
    }

    // View Skill buttons (delegated — cards are re-rendered on filter/search/sort).
    trendingGrid.addEventListener("click", function (e) {
      var btn = e.target.closest(".js-view-skill");
      if (!btn) return;
      selectSkill(btn.getAttribute("data-skill"));
    });

    // Request Session buttons (delegated — mentor cards are re-rendered per skill).
    var mentorsGridEl = document.getElementById("mentorsGrid");
    if (mentorsGridEl) {
      mentorsGridEl.addEventListener("click", function (e) {
        var btn = e.target.closest(".js-request");
        if (!btn) return;
        var key = btn.getAttribute("data-key");
        skillsState.requested[key] = !skillsState.requested[key];
        var sent = !!skillsState.requested[key];
        btn.classList.toggle("is-sent", sent);
        btn.textContent = sent ? "Request Sent" : "Request Session";
      });
    }

    renderTrending();
    renderMentors(skillsState.selectedSkill);
  }

  // ------------------------------------------------------------------------
  // Find Mentor page
  // ------------------------------------------------------------------------

  var fmResultsGrid = document.getElementById("fmResultsGrid");

  if (fmResultsGrid) {
    var FM_SORTS = [
      "Highest Rated",
      "Most Experienced",
      "Most Sessions Completed",
      "Recently Active"
    ];

    var FM_MENTORS = [
      {
        name: "Md. Nafis Rahman", initials: "NR", dept: "CSE", deptFull: "Computer Science & Engineering",
        year: "4th Year", level: "Advanced Mentor", category: "Programming",
        skills: ["JavaScript", "SQL", "Database Management"],
        bio: "Helping students understand web development and database concepts.",
        rating: 4.9, reviews: 86, sessions: 142, points: 520, availability: "Available Today",
        avatarBg: "#17324D", activeRank: 4,
        about: "Fourth-year CSE student. I have TA'd the web programming lab for two terms and most of my sessions are about untangling a bug someone has been stuck on for days.",
        teaches: ["HTML", "CSS", "JavaScript", "SQL"],
        helpsWith: ["Projects", "Assignments", "Concept Explanation"],
        helped: 96,
        slots: [{ day: "Monday", time: "6 PM - 9 PM" }, { day: "Wednesday", time: "7 PM - 10 PM" }]
      },
      {
        name: "Ayesha Siddika", initials: "AS", dept: "CSE", deptFull: "Computer Science & Engineering",
        year: "4th Year", level: "Advanced Mentor", category: "Database",
        skills: ["MySQL", "Database Design", "PHP"],
        bio: "Final-year student who has built three database-heavy course projects.",
        rating: 4.8, reviews: 54, sessions: 97, points: 465, availability: "Available This Week",
        avatarBg: "#2A7F78", activeRank: 2,
        about: "I spend most sessions on schema design - normalising tables, fixing joins and getting queries fast enough for a viva demo.",
        teaches: ["MySQL", "Database Design", "PHP", "ER Modelling"],
        helpsWith: ["Projects", "Assignments", "Exam Prep"],
        helped: 71,
        slots: [{ day: "Tuesday", time: "5 PM - 8 PM" }, { day: "Saturday", time: "10 AM - 1 PM" }]
      },
      {
        name: "Tanvir Ahmed", initials: "TA", dept: "EEE", deptFull: "Electrical & Electronic Engineering",
        year: "3rd Year", level: "Intermediate Mentor", category: "Programming",
        skills: ["Circuit Analysis", "MATLAB", "C++"],
        bio: "Walks juniors through circuit problems step by step, no shortcuts.",
        rating: 4.7, reviews: 41, sessions: 78, points: 420, availability: "Available Today",
        avatarBg: "#17324D", activeRank: 5,
        about: "Third-year EEE student. I keep a bank of past circuit problems and we work through them together rather than me solving them on the board.",
        teaches: ["Circuit Analysis", "MATLAB", "C++", "Signals"],
        helpsWith: ["Assignments", "Lab Reports", "Concept Explanation"],
        helped: 58,
        slots: [{ day: "Monday", time: "7 PM - 9 PM" }, { day: "Thursday", time: "6 PM - 9 PM" }]
      },
      {
        name: "Nusrat Jahan", initials: "NJ", dept: "BBA", deptFull: "Business Administration",
        year: "2nd Year", level: "Intermediate Mentor", category: "Business",
        skills: ["Business Analytics", "Presentation", "Excel"],
        bio: "Case competition finalist who helps teams sharpen their recommendation.",
        rating: 4.7, reviews: 29, sessions: 61, points: 295, availability: "Available This Week",
        avatarBg: "#2A7F78", activeRank: 3,
        about: "I coach case teams and presentation practice. Most of my help is about cutting analysis down to one clear recommendation.",
        teaches: ["Business Analytics", "Excel", "Presentation", "Case Framing"],
        helpsWith: ["Presentations", "Case Studies", "Projects"],
        helped: 44,
        slots: [{ day: "Wednesday", time: "4 PM - 7 PM" }, { day: "Friday", time: "11 AM - 2 PM" }]
      },
      {
        name: "Maisha Islam", initials: "MI", dept: "Others", deptFull: "Statistics",
        year: "3rd Year", level: "Intermediate Mentor", category: "Communication",
        skills: ["Research Methods", "Academic Writing"],
        bio: "Statistics student who helps with methodology and thesis write-ups.",
        rating: 4.6, reviews: 22, sessions: 47, points: 268, availability: "Available This Week",
        avatarBg: "#17324D", activeRank: 6,
        about: "I help with study design, sampling and reporting results so a thesis chapter reads clearly to an examiner.",
        teaches: ["Research Methods", "Academic Writing", "SPSS"],
        helpsWith: ["Thesis", "Assignments", "Concept Explanation"],
        helped: 35,
        slots: [{ day: "Sunday", time: "3 PM - 6 PM" }, { day: "Tuesday", time: "7 PM - 9 PM" }]
      },
      {
        name: "Farhan Kabir", initials: "FK", dept: "Architecture", deptFull: "Architecture",
        year: "4th Year", level: "Advanced Mentor", category: "Design",
        skills: ["Figma", "UI/UX", "AutoCAD"],
        bio: "Runs weekend crit sessions for studio and interface projects.",
        rating: 4.9, reviews: 37, sessions: 88, points: 410, availability: "Available Today",
        avatarBg: "#2A7F78", activeRank: 1,
        about: "Architecture student who also designs interfaces. My sessions are usually a crit: we look at your work, find what is unclear, and fix it together.",
        teaches: ["Figma", "UI/UX", "AutoCAD", "Portfolio Review"],
        helpsWith: ["Projects", "Portfolio", "Concept Explanation"],
        helped: 62,
        slots: [{ day: "Thursday", time: "6 PM - 9 PM" }, { day: "Saturday", time: "2 PM - 6 PM" }]
      }
    ];

    var FM_CATEGORIES = [
      {
        name: "Programming Mentors", count: 120, skill: "Programming",
        icon: '<path d="M7.2 6.5L3.8 10l3.4 3.5"></path><path d="M12.8 6.5L16.2 10l-3.4 3.5"></path><path d="M11.2 5l-2.4 10"></path>'
      },
      {
        name: "Database Mentors", count: 45, skill: "Database",
        icon: '<path d="M10 3.2c3.4 0 6 .9 6 2s-2.6 2-6 2-6-.9-6-2 2.6-2 6-2z"></path><path d="M4 5.2v9.6c0 1.1 2.6 2 6 2s6-.9 6-2V5.2"></path><path d="M4 10c0 1.1 2.6 2 6 2s6-.9 6-2"></path>'
      },
      {
        name: "Design Mentors", count: 65, skill: "Design",
        icon: '<path d="M13.4 3.6l3 3-8.2 8.2-3.6.8.8-3.6z"></path><path d="M11.6 5.4l3 3"></path>'
      },
      {
        name: "Academic Mentors", count: 80, skill: "Communication",
        icon: '<path d="M2.5 7.5L10 4l7.5 3.5L10 11z"></path><path d="M5.5 9.2v3.6c0 1.2 2 2.2 4.5 2.2s4.5-1 4.5-2.2V9.2"></path>'
      }
    ];

    var FM_ACTIVITY = [
      { name: "Rahim Ahmed", initials: "RA", action: "became available", time: "10 minutes ago", avatarBg: "#17324D" },
      { name: "Sakib Hasan", initials: "SH", action: "completed a SQL mentoring session", time: "2 hours ago", avatarBg: "#2A7F78" },
      { name: "Nafis Rahman", initials: "NR", action: "added JavaScript mentoring", time: "yesterday", avatarBg: "#17324D" },
      { name: "Nusrat Jahan", initials: "NJ", action: "opened two slots for this week", time: "2 days ago", avatarBg: "#2A7F78" }
    ];

    var fmState = {
      query: "",
      skill: "All Skills",
      dept: "All Departments",
      level: "All Levels",
      rating: "All Ratings",
      availability: "Any time",
      sortIndex: 0,
      selected: FM_MENTORS[0].name,
      requested: {}
    };

    function fmAvailBadgeClass(a) {
      return a === "Available Today" ? "status-confirmed" : "status-pending";
    }

    function fmVisibleMentors() {
      var q = fmState.query;
      var list = FM_MENTORS.filter(function (m) {
        if (fmState.skill !== "All Skills" && m.category !== fmState.skill) return false;
        if (fmState.dept !== "All Departments" && m.dept !== fmState.dept) return false;
        if (fmState.level !== "All Levels" && m.level !== fmState.level) return false;
        if (fmState.rating === "4.5+" && m.rating < 4.5) return false;
        if (fmState.rating === "4.0+" && m.rating < 4.0) return false;
        if (fmState.availability === "Available Today" && m.availability !== "Available Today") return false;
        // "Available Today" mentors are also available this week, so the
        // wider filter accepts either availability value.
        if (fmState.availability === "Available This Week" && m.availability !== "Available This Week" && m.availability !== "Available Today") return false;
        if (q) {
          var hay = (m.name + " " + m.deptFull + " " + m.skills.join(" ")).toLowerCase();
          if (hay.indexOf(q) === -1) return false;
        }
        return true;
      });

      var sort = FM_SORTS[fmState.sortIndex];
      if (sort === "Highest Rated") {
        list = list.slice().sort(function (a, b) { return b.rating - a.rating; });
      } else if (sort === "Most Experienced") {
        list = list.slice().sort(function (a, b) { return b.points - a.points; });
      } else if (sort === "Most Sessions Completed") {
        list = list.slice().sort(function (a, b) { return b.sessions - a.sessions; });
      } else if (sort === "Recently Active") {
        list = list.slice().sort(function (a, b) { return a.activeRank - b.activeRank; });
      }
      return list;
    }

    function fmMentorCardHtml(m) {
      var sent = !!fmState.requested[m.name];
      var selected = fmState.selected === m.name;
      return (
        '<article class="mentor-result-card' + (selected ? " is-selected" : "") + '" data-mentor="' + m.name + '">' +
          '<span class="mentor-avatar" style="width:64px;height:64px;font-size:20px;background:' + m.avatarBg + ';">' + m.initials + '</span>' +
          '<div class="flex-grow-1 min-w-0">' +
            '<div class="d-flex flex-wrap align-items-center gap-2">' +
              '<span class="heading-font fw-semibold" style="font-size:18px;color:#17324d;">' + m.name + '</span>' +
              '<span class="profile-role-badge">Verified Student Mentor</span>' +
            '</div>' +
            '<div class="mt-1" style="font-size:14px;color:#64748b;">' + m.deptFull + ' &middot; ' + m.year + ' &middot; ' + m.level + '</div>' +
            '<p class="mt-2 mb-0" style="font-size:15px;line-height:1.6;color:#17212b;">' + m.bio + '</p>' +
            '<div class="d-flex flex-wrap gap-2 mt-3">' +
              m.skills.map(function (s) { return '<span class="skill-chip">' + s + '</span>'; }).join("") +
            '</div>' +
            '<div class="d-flex flex-wrap gap-3 mt-3 align-items-center" style="font-size:14px;">' +
              '<span><span class="mentor-rating-star">&#9733;</span> <strong style="font-weight:700;">' + m.rating.toFixed(1) + '</strong> <span style="color:#64748b;">(' + m.reviews + ' reviews)</span></span>' +
              '<span style="color:#64748b;"><strong style="color:#17212b;">' + m.sessions + '</strong> sessions completed</span>' +
              '<span style="color:#64748b;"><strong style="color:#17212b;">' + m.points + '</strong> mentor points</span>' +
              '<span class="status-badge ' + fmAvailBadgeClass(m.availability) + '">' + m.availability + '</span>' +
            '</div>' +
          '</div>' +
          '<div class="d-flex flex-column gap-2" style="flex:0 1 170px;min-width:150px;">' +
            '<a href="profile.html" class="btn btn-outline-navy-card btn-sm js-view-mentor" data-mentor="' + m.name + '">View Profile</a>' +
            '<button type="button" class="request-btn js-request-mentor' + (sent ? " is-sent" : "") + '" data-mentor="' + m.name + '">' + (sent ? "Request Sent" : "Request Session") + '</button>' +
          '</div>' +
        '</article>'
      );
    }

    function fmRenderResults() {
      var list = fmVisibleMentors();
      var resultCount = document.getElementById("fmResultCount");
      if (resultCount) resultCount.textContent = list.length + (list.length === 1 ? " result" : " results");
      fmResultsGrid.innerHTML = list.length
        ? list.map(fmMentorCardHtml).join("")
        : '<div class="cert-empty">' +
            '<div class="fw-bold" style="font-size:16px;color:#17212b;">No mentors match these filters</div>' +
            '<p class="mx-auto mt-2 mb-0" style="max-width:360px;font-size:15px;line-height:1.55;color:#64748b;">Try a different skill or department, or widen the rating and availability filters.</p>' +
            '<button type="button" id="fmEmptyClear" class="btn btn-outline-navy-card mt-3">Clear all filters</button>' +
          '</div>';
    }

    function fmFeaturedCardHtml(m, i) {
      return (
        '<div class="col-sm-6 col-lg-4">' +
          '<div class="featured-mentor-card">' +
            '<div class="d-flex align-items-center gap-3">' +
              '<span class="rank-badge' + (i === 0 ? " rank-first" : "") + '">' + (i + 1) + '</span>' +
              '<span class="mentor-avatar" style="width:42px;height:42px;font-size:13px;background:' + m.avatarBg + ';">' + m.initials + '</span>' +
              '<div class="min-w-0">' +
                '<div class="heading-font fw-semibold" style="font-size:15px;color:#17324d;line-height:1.3;">' + m.name + '</div>' +
                '<div style="font-size:13px;color:#64748b;">' + m.dept + ' &middot; ' + m.year + '</div>' +
              '</div>' +
            '</div>' +
            '<div class="d-flex flex-wrap gap-2 mt-3">' +
              m.skills.slice(0, 2).map(function (s) { return '<span class="skill-chip">' + s + '</span>'; }).join("") +
            '</div>' +
            '<div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">' +
              '<div><div class="heading-font fw-semibold" style="font-size:16px;color:#17324d;">' + m.rating.toFixed(1) + ' &#9733;</div><div style="font-size:12px;color:#64748b;">Rating</div></div>' +
              '<div><div class="heading-font fw-semibold" style="font-size:16px;color:#17324d;">' + m.sessions + '</div><div style="font-size:12px;color:#64748b;">Sessions</div></div>' +
              '<div><div class="heading-font fw-semibold" style="font-size:16px;color:#2a7f78;">' + m.points + '</div><div style="font-size:12px;color:#64748b;">Points</div></div>' +
            '</div>' +
            '<button type="button" class="btn btn-outline-navy-card w-100 mt-3 js-select-mentor" data-mentor="' + m.name + '">View Profile</button>' +
          '</div>' +
        '</div>'
      );
    }

    function fmRenderFeatured() {
      var featured = FM_MENTORS.slice().sort(function (a, b) { return b.points - a.points; }).slice(0, 3);
      var grid = document.getElementById("fmFeaturedGrid");
      if (grid) grid.innerHTML = featured.map(fmFeaturedCardHtml).join("");
    }

    function fmCategoryRowHtml(c) {
      var active = fmState.skill === c.skill;
      return (
        '<button type="button" class="mentor-category-row' + (active ? " is-active" : "") + '" data-skill="' + c.skill + '">' +
          '<span class="category-icon-box"><svg width="19" height="19" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' + c.icon + '</svg></span>' +
          '<span class="flex-grow-1 min-w-0 text-start">' +
            '<span class="d-block" style="font-size:15px;font-weight:600;color:#17324d;">' + c.name + '</span>' +
            '<span class="d-block" style="font-size:13px;color:#64748b;">' + c.count + ' mentors</span>' +
          '</span>' +
        '</button>'
      );
    }

    function fmRenderCategories() {
      var wrap = document.getElementById("fmCategories");
      if (wrap) wrap.innerHTML = FM_CATEGORIES.map(fmCategoryRowHtml).join("");
    }

    function fmRenderActivity() {
      var wrap = document.getElementById("fmActivity");
      if (!wrap) return;
      wrap.innerHTML = FM_ACTIVITY.map(function (a, i) {
        var showLine = i < FM_ACTIVITY.length - 1;
        return (
          '<div class="skill-timeline-row">' +
            '<div class="skill-timeline-marker">' +
              '<span class="avatar-circle" style="width:32px;height:32px;font-size:11px;background:' + a.avatarBg + ';">' + a.initials + '</span>' +
              (showLine ? '<span class="skill-timeline-line"></span>' : '') +
            '</div>' +
            '<div class="flex-grow-1 min-w-0" style="padding-bottom:' + (showLine ? '20px' : '0') + ';">' +
              '<div style="font-size:14px;line-height:1.5;color:#17212b;"><strong style="font-weight:700;">' + a.name + '</strong> ' + a.action + '</div>' +
              '<div class="mt-1" style="font-size:13px;color:#64748b;">' + a.time + '</div>' +
            '</div>' +
          '</div>'
        );
      }).join("");
    }

    function fmRenderPreview() {
      var m = FM_MENTORS.filter(function (x) { return x.name === fmState.selected; })[0] || FM_MENTORS[0];
      var sent = !!fmState.requested[m.name];

      var setText = function (id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
      };

      var avatarEl = document.getElementById("fmPreviewAvatar");
      if (avatarEl) {
        avatarEl.textContent = m.initials;
        avatarEl.style.background = m.avatarBg;
      }
      setText("fmPreviewName", m.name);
      setText("fmPreviewDept", m.deptFull + " · " + m.year);
      setText("fmPreviewAbout", m.about);
      setText("fmPreviewSessions", m.sessions);
      setText("fmPreviewHelped", m.helped);
      setText("fmPreviewRating", m.rating.toFixed(1));

      var teachesWrap = document.getElementById("fmPreviewTeaches");
      if (teachesWrap) {
        teachesWrap.innerHTML = m.teaches.map(function (t) { return '<span class="skill-chip">' + t + '</span>'; }).join("");
      }

      var helpsWrap = document.getElementById("fmPreviewHelps");
      if (helpsWrap) {
        helpsWrap.innerHTML = m.helpsWith.map(function (h) { return '<span class="helps-chip">' + h + '</span>'; }).join("");
      }

      var slotsWrap = document.getElementById("fmPreviewSlots");
      if (slotsWrap) {
        slotsWrap.innerHTML = m.slots.map(function (s) {
          return (
            '<div class="mini-row" style="padding:0;border-top:0;">' +
              '<span style="font-size:15px;font-weight:500;color:#17212b;">' + s.day + '</span>' +
              '<span class="mono-font ms-auto" style="font-size:13px;color:#64748b;">' + s.time + '</span>' +
            '</div>'
          );
        }).join("");
      }

      var requestBtn = document.getElementById("fmPreviewRequest");
      if (requestBtn) {
        requestBtn.textContent = sent ? "Request Sent" : "Request Learning Session";
        requestBtn.classList.toggle("is-sent", sent);
      }
    }

    function fmSelectMentor(name) {
      fmState.selected = name;
      fmRenderPreview();
      fmRenderResults();
    }

    function fmToggleRequest(name) {
      fmState.requested[name] = !fmState.requested[name];
      fmRenderResults();
      fmRenderPreview();
    }

    // Search input.
    var fmSearchInput = document.getElementById("fmSearchInput");
    if (fmSearchInput) {
      fmSearchInput.addEventListener("input", function () {
        fmState.query = fmSearchInput.value.trim().toLowerCase();
        fmRenderResults();
      });
    }

    // Filter selects.
    var fmSkillSelect = document.getElementById("fmSkill");
    if (fmSkillSelect) {
      fmSkillSelect.addEventListener("change", function () {
        fmState.skill = fmSkillSelect.value;
        fmRenderCategories();
        fmRenderResults();
      });
    }
    var fmDeptSelect = document.getElementById("fmDept");
    if (fmDeptSelect) {
      fmDeptSelect.addEventListener("change", function () {
        fmState.dept = fmDeptSelect.value;
        fmRenderResults();
      });
    }
    var fmLevelSelect = document.getElementById("fmLevel");
    if (fmLevelSelect) {
      fmLevelSelect.addEventListener("change", function () {
        fmState.level = fmLevelSelect.value;
        fmRenderResults();
      });
    }
    var fmRatingSelect = document.getElementById("fmRating");
    if (fmRatingSelect) {
      fmRatingSelect.addEventListener("change", function () {
        fmState.rating = fmRatingSelect.value;
        fmRenderResults();
      });
    }

    // Availability pills.
    var fmAvailabilityWrap = document.getElementById("fmAvailability");
    if (fmAvailabilityWrap) {
      var fmAvailabilityBtns = fmAvailabilityWrap.querySelectorAll(".filter-pill");
      fmAvailabilityBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
          fmAvailabilityBtns.forEach(function (b) { b.classList.remove("active"); });
          btn.classList.add("active");
          fmState.availability = btn.getAttribute("data-availability");
          fmRenderResults();
        });
      });
    }

    // Filters show/hide toggle.
    var fmFilterToggle = document.getElementById("fmFilterToggle");
    var fmFiltersPanel = document.getElementById("fmFiltersPanel");
    if (fmFilterToggle && fmFiltersPanel) {
      fmFilterToggle.addEventListener("click", function () {
        var hidden = fmFiltersPanel.classList.toggle("d-none");
        fmFilterToggle.setAttribute("aria-expanded", String(!hidden));
        var label = document.getElementById("fmFilterToggleLabel");
        if (label) label.textContent = hidden ? "Show filters" : "Hide filters";
      });
    }

    // Clear filters.
    function fmClearFilters() {
      fmState.query = "";
      fmState.skill = "All Skills";
      fmState.dept = "All Departments";
      fmState.level = "All Levels";
      fmState.rating = "All Ratings";
      fmState.availability = "Any time";
      if (fmSearchInput) fmSearchInput.value = "";
      if (fmSkillSelect) fmSkillSelect.value = "All Skills";
      if (fmDeptSelect) fmDeptSelect.value = "All Departments";
      if (fmLevelSelect) fmLevelSelect.value = "All Levels";
      if (fmRatingSelect) fmRatingSelect.value = "All Ratings";
      if (fmAvailabilityWrap) {
        fmAvailabilityWrap.querySelectorAll(".filter-pill").forEach(function (b) {
          b.classList.toggle("active", b.getAttribute("data-availability") === "Any time");
        });
      }
      fmRenderCategories();
      fmRenderResults();
    }

    var fmClearFiltersBtn = document.getElementById("fmClearFilters");
    if (fmClearFiltersBtn) fmClearFiltersBtn.addEventListener("click", fmClearFilters);

    // Sort cycle button.
    var fmSortBtn = document.getElementById("fmSortBtn");
    if (fmSortBtn) {
      var fmSortLabel = fmSortBtn.querySelector(".sort-label");
      fmSortBtn.addEventListener("click", function () {
        fmState.sortIndex = (fmState.sortIndex + 1) % FM_SORTS.length;
        if (fmSortLabel) fmSortLabel.textContent = FM_SORTS[fmState.sortIndex];
        fmRenderResults();
      });
    }

    // Category rows (delegated — re-rendered on filter change).
    var fmCategoriesWrap = document.getElementById("fmCategories");
    if (fmCategoriesWrap) {
      fmCategoriesWrap.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-skill]");
        if (!btn) return;
        fmState.skill = btn.getAttribute("data-skill");
        if (fmSkillSelect) fmSkillSelect.value = fmState.skill;
        fmRenderCategories();
        fmRenderResults();
      });
    }

    // Result cards (delegated — re-rendered on every filter/sort change).
    fmResultsGrid.addEventListener("click", function (e) {
      var viewBtn = e.target.closest(".js-view-mentor");
      if (viewBtn) {
        fmSelectMentor(viewBtn.getAttribute("data-mentor"));
        return;
      }
      var requestBtn = e.target.closest(".js-request-mentor");
      if (requestBtn) {
        e.preventDefault();
        fmToggleRequest(requestBtn.getAttribute("data-mentor"));
        return;
      }
      var emptyClear = e.target.closest("#fmEmptyClear");
      if (emptyClear) {
        fmClearFilters();
      }
    });

    // Featured mentor cards (delegated).
    var fmFeaturedGrid = document.getElementById("fmFeaturedGrid");
    if (fmFeaturedGrid) {
      fmFeaturedGrid.addEventListener("click", function (e) {
        var btn = e.target.closest(".js-select-mentor");
        if (!btn) return;
        fmSelectMentor(btn.getAttribute("data-mentor"));
        var previewSection = document.getElementById("fmPreview");
        if (previewSection) previewSection.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    }

    // Preview panel request button.
    var fmPreviewRequest = document.getElementById("fmPreviewRequest");
    if (fmPreviewRequest) {
      fmPreviewRequest.addEventListener("click", function () {
        fmToggleRequest(fmState.selected);
      });
    }

    fmRenderCategories();
    fmRenderActivity();
    fmRenderResults();
    fmRenderFeatured();
    fmRenderPreview();
  }

  // ------------------------------------------------------------------------
  // Mentor Profile page
  // ------------------------------------------------------------------------

  // Save Mentor toggle.
  var saveMentorBtn = document.getElementById("saveMentorBtn");
  if (saveMentorBtn) {
    var saveMentorLabel = document.getElementById("saveMentorLabel");
    saveMentorBtn.addEventListener("click", function () {
      var saved = saveMentorBtn.classList.toggle("is-saved");
      saveMentorBtn.setAttribute("aria-pressed", saved);
      if (saveMentorLabel) {
        saveMentorLabel.textContent = saved ? "Mentor Saved" : "Save Mentor";
      }
    });
  }

  // Profile tabs — switch which panel (Overview / Reviews / Sessions /
  // Contributions) is visible. The request form, similar mentors and final
  // CTA sit outside these panels and stay visible on every tab.
  var mpTabs = document.querySelectorAll(".mp-tab");
  if (mpTabs.length) {
    var mpPanels = document.querySelectorAll(".mp-panel");
    mpTabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var target = tab.getAttribute("data-tab");
        mpTabs.forEach(function (t) {
          t.classList.toggle("active", t === tab);
          t.setAttribute("aria-pressed", t === tab);
        });
        mpPanels.forEach(function (panel) {
          panel.hidden = panel.getAttribute("data-panel") !== target;
        });
      });
    });
  }

  // Request a Learning Session form (frontend only — no request is actually sent).
  var mentorRequestForm = document.getElementById("mentorRequestForm");
  if (mentorRequestForm) {
    var rqSkill = document.getElementById("rqSkill");
    var rqDate = document.getElementById("rqDate");
    var rqMessage = document.getElementById("rqMessage");
    var rqMsgCount = document.getElementById("rqMsgCount");
    var rqFormError = document.getElementById("rqFormError");
    var rqSuccess = document.getElementById("rqSuccess");
    var rqSuccessSkill = document.getElementById("rqSuccessSkill");
    var rqSubmitBtn = document.getElementById("rqSubmitBtn");
    var rqSendTimer = null;

    if (rqMessage && rqMsgCount) {
      rqMessage.addEventListener("input", function () {
        rqMsgCount.textContent = rqMessage.value.length + " / 400";
      });
    }

    mentorRequestForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!rqDate.value) {
        rqFormError.textContent = "Choose a preferred date for the session.";
        rqFormError.classList.remove("d-none");
        rqDate.focus();
        return;
      }
      if (!rqMessage.value.trim()) {
        rqFormError.textContent =
          "Add a short message so the mentor knows what you want to learn.";
        rqFormError.classList.remove("d-none");
        rqMessage.focus();
        return;
      }

      rqFormError.classList.add("d-none");
      rqSubmitBtn.disabled = true;
      rqSubmitBtn.textContent = "Sending request...";

      clearTimeout(rqSendTimer);
      rqSendTimer = setTimeout(function () {
        if (rqSuccessSkill) rqSuccessSkill.textContent = rqSkill.value;
        if (rqSuccess) rqSuccess.classList.remove("d-none");
        rqSubmitBtn.disabled = false;
        rqSubmitBtn.textContent = "Update Request";
      }, 1000);
    });
  }
});
