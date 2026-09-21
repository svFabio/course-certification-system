<style>
    /*
     * UMSS Institutional Design Tokens
     * All color values correspond to the official token palette (AGENTS.md §6).
     * --umss-gray-200 and --umss-gray-300 are neutral UI-chrome values, not
     * institutional brand colors; kept as CSS variables to avoid scattering raw
     * hex throughout the stylesheet.
     */
    :root {
        --umss-navy:       #0E2E5F;
        --umss-navy-dark:  #0A2247;
        --umss-red:        #E01D2E;
        --umss-white:      #FFFFFF;
        --umss-black:      #121212;
        --umss-gray-100:   #F5F5F5;
        --umss-gray-700:   #4A4A4A;
        /* UI-chrome neutral borders — not institutional brand colors */
        --umss-gray-200:   #E5E5E5;
        --umss-gray-300:   #D6D6D6;
    }

    body, .fi-body {
        background-color: var(--umss-gray-100) !important;
        font-family: 'Montserrat', Arial, sans-serif !important;
        color: var(--umss-black) !important;
    }
    .fi-simple-layout {
        background-color: var(--umss-gray-100) !important;
    }
    .fi-simple-main {
        background-color: var(--umss-white) !important;
        border: 1px solid var(--umss-gray-200) !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.05) !important;
        max-width: 440px !important;
        padding: 2.5rem 2rem !important;
    }
    .fi-simple-header-heading {
        color: var(--umss-navy) !important;
        font-weight: 600 !important;
        font-size: 1.5rem !important;
    }
    .fi-simple-header-subheading {
        color: var(--umss-gray-700) !important;
        font-size: 0.875rem !important;
    }
    .fi-input-wrp {
        border: 1px solid var(--umss-gray-300) !important;
        border-radius: 8px !important;
        background-color: var(--umss-white) !important;
        box-shadow: none !important;
    }
    .fi-input-wrp:focus-within {
        border-color: var(--umss-navy) !important;
        box-shadow: 0 0 0 1px var(--umss-navy) !important;
    }
    .fi-btn.fi-btn-color-primary {
        background-color: var(--umss-navy) !important;
        color: var(--umss-white) !important;
        border-radius: 8px !important;
        height: 42px !important;
        font-weight: 500 !important;
        transition: background-color 150ms ease-in-out !important;
    }
    .fi-btn.fi-btn-color-primary:hover {
        background-color: var(--umss-navy-dark) !important;
    }
    .fi-topbar {
        border-top: 4px solid var(--umss-navy) !important;
    }
    .fi-sidebar {
        border-top: 4px solid var(--umss-navy) !important;
    }
    .fi-logo {
        color: var(--umss-navy) !important;
        font-weight: 700 !important;
    }
</style>
