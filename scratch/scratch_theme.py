import re

file_path = "member/index.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update CSS Variables
old_vars = """        :root {
            --red: #c41230;
            --red-dark: #7a001b;
            --gold: #ffc72c;
            --gold-dark: #e6a800;
            --ink: #0f172a;
            --muted: #64748b;
            --surface: #fff;
            --cream: #fffcf5;
        }"""
new_vars = """        :root {
            --red: #c41230;
            --red-dark: #7a001b;
            --gold: #ffc72c;
            --gold-dark: #e6a800;
            --ink: #0f172a;
            
            /* Light Theme */
            --bg-main: #fffcf5;
            --bg-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.08);
            --card-bg: rgba(255,255,255,0.9);
            --card-border: rgba(0,0,0,0.08);
            --nav-bg: rgba(255, 252, 245, 1);
            --hero-bg: #fffcf5;
            --hero-overlay: linear-gradient(to bottom, rgba(255, 252, 245, 0) 0%, rgba(255, 252, 245, 1) 90%);
        }

        [data-theme="dark"] {
            /* Dark Theme */
            --bg-main: #0a0a0f;
            --bg-surface: #12121a;
            --text-main: #ffffff;
            --text-muted: rgba(255,255,255,0.5);
            --border-color: rgba(255,255,255,0.08);
            --card-bg: rgba(255,255,255,0.04);
            --card-border: rgba(255,255,255,0.1);
            --nav-bg: rgba(10, 10, 20, 1);
            --hero-bg: #0a0a0f;
            --hero-overlay: linear-gradient(to bottom, rgba(10,10,15,0) 0%, rgba(10,10,15,1) 90%);
        }"""
content = content.replace(old_vars, new_vars)

# 2. Update Body CSS
old_body = """        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #0a0a0f;
            color: #fff;
            min-height: 100svh;
            overflow-x: hidden;
        }"""
new_body = """        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100svh;
            overflow-x: hidden;
            transition: background 0.3s, color 0.3s;
        }"""
content = content.replace(old_body, new_body)

# 3. Update Navbar CSS
content = re.sub(r'background:\s*rgba\(10,10,20,0\.7\);', 'background: var(--nav-bg);', content)
content = re.sub(r'border-bottom:\s*1px solid rgba\(255,255,255,0\.06\);', 'border-bottom: 1px solid var(--border-color);', content)
content = re.sub(r'color:\s*#fff;\s*\}\s*\.nav-logo img', 'color: var(--text-main); } .nav-logo img', content)
content = re.sub(r'color:\s*#fff;\s*font-weight:\s*700;\s*font-size:\s*12px;\s*background:\s*rgba\(255,255,255,0\.08\);', 'color: var(--text-main); font-weight: 700; font-size: 12px; background: var(--border-color);', content)

# 4. Update Other Elements to use variables
content = re.sub(r'background:\s*rgba\(255,255,255,0\.04\);', 'background: var(--card-bg);', content)
content = re.sub(r'border:\s*1px solid rgba\(255,255,255,0\.1\);', 'border: 1px solid var(--card-border);', content)
content = re.sub(r'border:\s*1px solid rgba\(255,255,255,0\.08\);', 'border: 1px solid var(--card-border);', content)
content = re.sub(r'border-bottom:\s*1px solid rgba\(255,255,255,0\.06\);', 'border-bottom: 1px solid var(--border-color);', content)
content = re.sub(r'border-top:\s*1px solid rgba\(255,255,255,0\.06\);', 'border-top: 1px solid var(--border-color);', content)
content = re.sub(r'color:\s*#fff;', 'color: var(--text-main);', content)
content = re.sub(r'color:\s*rgba\(255,255,255,0\.4\);', 'color: var(--text-muted);', content)
content = re.sub(r'color:\s*rgba\(255,255,255,0\.45\);', 'color: var(--text-muted);', content)
content = re.sub(r'color:\s*rgba\(255,255,255,0\.5\);', 'color: var(--text-muted);', content)
content = re.sub(r'color:\s*rgba\(255,255,255,0\.55\);', 'color: var(--text-muted);', content)
content = re.sub(r'color:\s*rgba\(255,255,255,0\.8\);', 'color: var(--text-main);', content)
content = re.sub(r'color:\s*rgba\(255,255,255,0\.85\);', 'color: var(--text-main);', content)
content = re.sub(r'background:\s*linear-gradient\(145deg,\s*rgba\(255,255,255,0\.06\)\s*0%,\s*rgba\(255,255,255,0\.02\)\s*100%\);', 'background: var(--card-bg);', content)
content = re.sub(r'background:\s*rgba\(0,0,0,0\.2\);', 'background: var(--card-bg);', content)

# 5. Fix Layout (Move Hero below navbar)
# Find the exact absolute div:
hero_regex = r'<!-- Full Width Hero Background -->\s*<div style="position: absolute; top: 0; left: 0; width: 100%; height: 80vh; min-height: 600px; z-index: 0; background-color: #0a0a0f; background-image: url\(\'../public/assets/images/member-hero.jpeg\?v=<\?= time\(\) \?>\'\); background-size: contain; background-position: top center; background-repeat: no-repeat; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px;">\s*<!-- Dark overlay for text readability -->\s*<div style="position: absolute; inset: 0; background: linear-gradient\(to bottom, rgba\(10,10,15,0\.7\) 0%, rgba\(10,10,15,1\) 90%\); border-bottom-left-radius: 40px; border-bottom-right-radius: 40px;"></div>\s*</div>'

new_hero = """<!-- Full Width Hero Background (Relative to document flow) -->
<div class="hero-image-wrapper" style="position: relative; width: 100%; height: 60vh; min-height: 400px; z-index: 0; background-color: var(--hero-bg); background-image: url('../public/assets/images/member-hero.jpeg?v=<?= time() ?>'); background-size: contain; background-position: top center; background-repeat: no-repeat; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; margin-top: 0;">
    <!-- Fade out overlay -->
    <div style="position: absolute; inset: 0; background: var(--hero-overlay); border-bottom-left-radius: 40px; border-bottom-right-radius: 40px;"></div>
</div>"""

content = re.sub(hero_regex, "", content)
navbar_regex = r'(<nav class="navbar">.*?</nav>)'
content = re.sub(navbar_regex, r'\1\n\n' + new_hero, content, flags=re.DOTALL)


# Insert Theme Toggle JS and HTML
toggle_html = """
        <button id="themeToggle" class="btn-nav" style="margin-right:8px; cursor:pointer; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" onclick="toggleTheme()">
            <i class="fa-solid fa-moon"></i>
        </button>"""
content = content.replace('<div class="nav-badge">', toggle_html + '\n        <div class="nav-badge">')

toggle_js = """
<script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') === 'dark';
        if(isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            document.querySelector('#themeToggle').innerHTML = '<i class="fa-solid fa-moon"></i>';
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            document.querySelector('#themeToggle').innerHTML = '<i class="fa-solid fa-sun"></i>';
        }
    }
    // Init theme
    if(localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        window.addEventListener('DOMContentLoaded', () => {
            const btn = document.querySelector('#themeToggle');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-sun"></i>';
        });
    } else {
        // default to light as requested
    }
</script>
"""
content = content.replace('</body>', toggle_js + '\n</body>')

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
