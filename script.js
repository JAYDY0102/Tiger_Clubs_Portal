document.querySelectorAll('nav.nav a').forEach(link => {
    link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        if (href && href.startsWith('#') && !isLoggedIn) {
            e.preventDefault();
            window.location.href = 'auth/require-login.php'; // or '/auth/require-login.php' if you prefer absolute
        }
    });
});

let clubs = [];

const grid = document.getElementById("clubGrid");
const drawerContent = document.getElementById("drawerContent");
const layout = document.querySelector(".layout");
const searchInput = document.getElementById("searchInput");
const closeDrawer = document.getElementById("closeDrawer");

const typeFilters = document.getElementById("typeFilters");
const dayFilters = document.getElementById("dayFilters");

const currentUser = {
    role: "guest",
    email: ""
};

let selectedType = "all";
let selectedDay = "all";
let searchTerm = "";
let editingClub = null;

const EMPTY_DRAWER_HTML = `
    <div class="drawer-empty">
        <h3>Select a club</h3>
        <p>Click a club card to view its details here.</p>
    </div>
`;

function imageApiUrl(path, w, h) {
    if (!path) return "";
    if (path.startsWith("http")) return path;
    return `/api/image.php?path=${encodeURIComponent(path)}&w=${w}&h=${h}`;
}

function canManageClub(club) {
    if (currentUser.role === "admin") return true;
    if (currentUser.role !== "executive") return false;
    return Array.isArray(club.executiveEmails) && club.executiveEmails.includes(currentUser.email);
}

function setImageForLazyLoad(img, src, alt) {
    img.dataset.src = src;
    img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="400 300"%3E%3C/svg%3E';
    img.alt = alt;
}

function renderNoClubsFound() {
    grid.innerHTML = `<p style="color:#667085;padding:12px;">No clubs found.</p>`;
}

function renderCards(list) {
    grid.innerHTML = "";
    const template = document.getElementById("clubCardTemplate");

    if (!list.length) {
        renderNoClubsFound();
        return;
    }

    list.forEach((club) => {
        const node = template.content.cloneNode(true);
        const card = node.querySelector(".club-card");
        const img = node.querySelector(".club-image");

        setImageForLazyLoad(img, imageApiUrl(club.image, 400, 300), club.name);

        node.querySelector(".club-name").textContent = club.name;
        node.querySelector(".club-type").textContent = club.type;
        node.querySelector(".club-members").textContent = `${club.members} members`;
        node.querySelector(".club-summary").textContent = club.summary;
        node.querySelector(".type-tag").textContent = club.type;
        node.querySelector(".day-tag").textContent = club.day;

        card.addEventListener("click", () => openDrawer(club));
        grid.appendChild(node);
    });

    lazyLoadImages();
}

function lazyLoadImages() {
    const images = document.querySelectorAll("img[data-src]");

    if ("IntersectionObserver" in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute("data-src");
                observer.unobserve(img);
            });
        }, { rootMargin: "50px" });

        images.forEach((img) => imageObserver.observe(img));
        return;
    }

    images.forEach((img) => {
        img.src = img.dataset.src;
        img.removeAttribute("data-src");
    });
}

function applyFilters() {
    const search = searchTerm.toLowerCase();

    const filtered = clubs.filter((club) => {
        const matchesSearch =
            club.name.toLowerCase().includes(search) ||
            club.type.toLowerCase().includes(search) ||
            club.summary.toLowerCase().includes(search);

        const matchesType = selectedType === "all" || club.type === selectedType;
        const matchesDay = selectedDay === "all" || club.day === selectedDay;

        return matchesSearch && matchesType && matchesDay;
    });

    renderCards(filtered);
}

function setActiveChip(container, value) {
    container.querySelectorAll(".chip").forEach((chip) => {
        chip.classList.toggle("active", chip.dataset.filter === value);
    });
}

typeFilters.addEventListener("click", (e) => {
    const btn = e.target.closest(".chip");
    if (!btn) return;
    selectedType = btn.dataset.filter;
    setActiveChip(typeFilters, selectedType);
    applyFilters();
});

dayFilters.addEventListener("click", (e) => {
    const btn = e.target.closest(".chip");
    if (!btn) return;
    selectedDay = btn.dataset.filter;
    setActiveChip(dayFilters, selectedDay);
    applyFilters();
});

searchInput.addEventListener("input", (e) => {
    searchTerm = e.target.value.trim();
    applyFilters();
});

function toggleEditMode(club) {
    if (editingClub === null) {
        editingClub = club;
        renderEditForm(club);
        return;
    }

    editingClub = null;
    openDrawer(club);
}

function createEditFormHtml(club) {
    return `
        <div style="padding: 26px;">
            <h3>Edit ${club.name}</h3>
            <form id="clubEditForm" style="display: grid; gap: 14px;">
                <label>
                    <strong>Club Name</strong>
                    <input type="text" name="name" value="${club.name}" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <label>
                    <strong>Type</strong>
                    <select name="type" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;">
                        <option value="STEM" ${club.type === "STEM" ? "selected" : ""}>STEM</option>
                        <option value="Academic" ${club.type === "Academic" ? "selected" : ""}>Academic</option>
                        <option value="Arts & Culture" ${club.type === "Arts & Culture" ? "selected" : ""}>Arts & Culture</option>
                        <option value="Community Service" ${club.type === "Community Service" ? "selected" : ""}>Community Service</option>
                        <option value="Sports" ${club.type === "Sports" ? "selected" : ""}>Sports</option>
                        <option value="Other" ${club.type === "Other" ? "selected" : ""}>Other</option>
                    </select>
                </label>

                <label>
                    <strong>Meeting Day</strong>
                    <select name="day" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;">
                        <option value="Monday" ${club.day === "Monday" ? "selected" : ""}>Monday</option>
                        <option value="Wednesday" ${club.day === "Wednesday" ? "selected" : ""}>Wednesday</option>
                        <option value="Thursday (A)" ${club.day === "Thursday (A)" ? "selected" : ""}>Thursday (A)</option>
                        <option value="Thursday (B)" ${club.day === "Thursday (B)" ? "selected" : ""}>Thursday (B)</option>
                        <option value="Friday" ${club.day === "Friday" ? "selected" : ""}>Friday</option>
                        <option value="Other" ${club.day === "Other" ? "selected" : ""}>Other</option>
                    </select>
                </label>

                <label>
                    <strong>Summary</strong>
                    <textarea name="summary" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px; min-height: 60px;">${club.summary}</textarea>
                </label>

                <label>
                    <strong>About</strong>
                    <textarea name="about" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px; min-height: 80px;">${club.about ?? ""}</textarea>
                </label>

                <label>
                    <strong>Advisor</strong>
                    <input type="text" name="advisor" value="${club.advisor ?? ""}" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <label>
                    <strong>Contact Email</strong>
                    <input type="email" name="contactEmail" value="${club.contactEmail ?? ""}" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <label>
                    <strong>Instagram</strong>
                    <input type="text" name="instagram" value="${club.instagram ?? ""}" placeholder="@handle" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <label>
                    <strong>Website</strong>
                    <input type="url" name="website" value="${club.website ?? ""}" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <label>
                    <strong>Meeting Location</strong>
                    <input type="text" name="location" value="${club.meeting?.location ?? ""}" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <label>
                    <strong>Meeting Time</strong>
                    <input type="text" name="time" value="${club.meeting?.time ?? ""}" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px;" />
                </label>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <button type="button" class="btn btn-ghost" onclick="toggleEditMode(editingClub)" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    `;
}
function buildUpdatePayload(form, club) {
    const payload = new FormData();
    payload.append("clubDir", club.dirName);
    payload.append("data", JSON.stringify({
        name: form.name.value,
        type: form.type.value,
        day: form.day.value,
        members: form.members.value,
        summary: form.summary.value,
        about: form.about.value,
        advisor: form.advisor.value,
        contactEmail: form.contactEmail.value,
        instagram: form.instagram.value,
        website: form.website.value,
        meeting: {
            day: form.day.value,
            location: form.location.value,
            time: form.time.value
        },
        posts: club.posts ?? []
    }));
    return payload;
}

async function submitEditForm(e, club, form) {
    e.preventDefault();

    const payload = buildUpdatePayload(form, club);

    try {
        const response = await fetch("api/update-club.php", {
            method: "POST",
            body: payload
        });

        const result = await response.json();
        if (response.ok) {
            alert("Club updated successfully!");
            editingClub = null;
            await loadClubs();
            return;
        }

        alert("Error: " + result.error);
    } catch (error) {
        console.error(error);
        alert("Failed to update club");
    }
}

function renderEditForm(club) {
    drawerContent.innerHTML = createEditFormHtml(club);
    const form = document.getElementById("clubEditForm");
    form.addEventListener("submit", (e) => submitEditForm(e, club, form));
}

function renderContactSection(club) {
    if (!club.advisor && !club.contactEmail && !club.instagram && !club.website) {
        return "";
    }

    return `
        <div class="section-box">
            <h4>Contact Info</h4>
            ${club.advisor ? `<p><strong>Advisor:</strong> ${club.advisor}</p>` : ""}
            ${club.contactEmail ? `<p><strong>Email:</strong> <a href="mailto:${club.contactEmail}">${club.contactEmail}</a></p>` : ""}
            ${club.instagram ? `<p><strong>Instagram:</strong> <a href="https://instagram.com/${club.instagram.replace("@", "")}" target="_blank">${club.instagram}</a></p>` : ""}
            ${club.website ? `<p><strong>Website:</strong> <a href="${club.website}" target="_blank">${club.website}</a></p>` : ""}
        </div>
    `;
}

function renderPostsSection(club) {
    const posts = (club.posts ?? [])
        .map((post) => `
            <div class="post">
                <div class="meta">${post.date}</div>
                <div>${post.text}</div>
            </div>
        `)
        .join("");

    return `
        <div class="section-box">
            <h4>Posts</h4>
            <div class="post-list">${posts}</div>
        </div>
    `;
}

function openDrawer(club) {
    const showManageButton = canManageClub(club);
    const bannerUrl = imageApiUrl(club.image, 840, 420);
    let contactHtml = "";
    if (isLoggedIn){
        contactHtml = renderContactSection(club);
    }
    drawerContent.innerHTML = `
        <div class="drawer-banner">
            <img src="${bannerUrl}" alt="${club.name}" />
        </div>

        <h3 class="drawer-title">${club.name}</h3>

        <div class="drawer-sub">
            <span class="pill">${club.type}</span>
            <span class="pill">${club.day}</span>
            <span class="pill">${club.members} members</span>
        </div>

        <div class="drawer-actions">
            <button class="btn btn-primary" type="button">Follow Club</button>
            ${showManageButton ? `<button class="btn btn-ghost" type="button" onclick="toggleEditMode(editingClub || ${JSON.stringify(club).replace(/"/g, "&quot;")})">Edit Club</button>` : ""}
        </div>

        <div class="section-box">
            <h4>About This Club</h4>
            <p>${club.about ?? "No description available."}</p>
        </div>

        <div class="section-box">
            <h4>Meeting Information</h4>
            <p><strong>Day:</strong> ${club.meeting?.day ?? "TBA"}</p>
            <p><strong>Location:</strong> ${club.meeting?.location ?? "TBA"}</p>
            <p><strong>Time:</strong> ${club.meeting?.time ?? "TBA"}</p>
        </div>
        
        ${contactHtml}
        ${renderPostsSection(club)}
    `;

    layout.classList.add("drawer-open");
    drawerContent.parentElement.scrollTop = 0;
}

closeDrawer.addEventListener("click", () => {
    layout.classList.remove("drawer-open");
    window.setTimeout(() => {
        drawerContent.innerHTML = EMPTY_DRAWER_HTML;
    }, 320);
});

async function loadClubs() {
    try {
        const response = await fetch("api/clubs.php");
        if (!response.ok) {
            console.error(`Failed to load clubs: ${response.status}`);
            grid.innerHTML = `<p style="color:#b91c1c;padding:12px;">Could not load club data.</p>`;
            return;
        }

        clubs = await response.json();
        applyFilters();
    } catch (error) {
        console.error("Could not load club data:", error);
        grid.innerHTML = `<p style="color:#b91c1c;padding:12px;">Could not load club data.</p>`;
    }
}

loadClubs();