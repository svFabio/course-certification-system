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
        /* Semantic status tokens */
        --umss-green:      #16A34A;
        --umss-green-dark: #15803D;
        --umss-green-light:#DCFCE7;
        --umss-amber:      #D97706;
        --umss-amber-dark: #B45309;
        --umss-amber-light:#FEF3C7;
        --umss-sky:        #0284C7;
        --umss-sky-dark:   #0369A1;
        --umss-sky-light:  #E0F2FE;
    }

    body, .fi-body {
        background-color: var(--umss-gray-100) !important;
        font-family: 'Montserrat', Arial, sans-serif !important;
        color: var(--umss-black) !important;
        -webkit-font-smoothing: antialiased;
    }
    .fi-simple-layout {
        background-color: var(--umss-gray-100) !important;
    }
    .fi-simple-main {
        background-color: var(--umss-white) !important;
        border: 1px solid var(--umss-gray-200) !important;
        border-radius: 16px !important;
        box-shadow: 0 4px 16px -2px rgba(14, 46, 95, 0.08) !important;
        max-width: 440px !important;
        padding: 2.5rem 2rem !important;
    }
    .fi-simple-header-heading {
        color: var(--umss-navy) !important;
        font-weight: 700 !important;
        font-size: 1.625rem !important;
        letter-spacing: -0.02em !important;
    }
    .fi-simple-header-subheading {
        color: var(--umss-gray-700) !important;
        font-size: 0.875rem !important;
    }
    .fi-input-wrp {
        border: 1px solid var(--umss-gray-300) !important;
        border-radius: 8px !important;
        background-color: var(--umss-white) !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
        transition: border-color 150ms ease, box-shadow 150ms ease !important;
    }
    .fi-input-wrp:focus-within {
        border-color: var(--umss-navy) !important;
        box-shadow: 0 0 0 1.5px var(--umss-navy) !important;
    }
    .fi-btn.fi-btn-color-primary {
        background-color: var(--umss-navy) !important;
        color: var(--umss-white) !important;
        border-radius: 8px !important;
        height: 40px !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        letter-spacing: 0.01em !important;
        box-shadow: 0 1px 3px 0 rgba(14, 46, 95, 0.2) !important;
        transition: all 150ms ease-in-out !important;
    }
    .fi-btn.fi-btn-color-primary:hover {
        background-color: var(--umss-navy-dark) !important;
        box-shadow: 0 3px 6px -1px rgba(10, 34, 71, 0.25) !important;
        transform: translateY(-0.5px);
    }
    .fi-topbar {
        border-top: 4px solid var(--umss-navy) !important;
        border-bottom: 1px solid var(--umss-gray-200) !important;
        background-color: var(--umss-white) !important;
    }
    .fi-sidebar {
        border-top: 4px solid var(--umss-navy) !important;
        border-right: 1px solid var(--umss-gray-200) !important;
        background-color: var(--umss-white) !important;
    }
    .fi-sidebar-item-active .fi-sidebar-item-button {
        background-color: rgba(14, 46, 95, 0.06) !important;
        color: var(--umss-navy) !important;
        font-weight: 600 !important;
        border-left: 3px solid var(--umss-navy) !important;
    }
    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: var(--umss-navy) !important;
    }
    .fi-logo {
        color: var(--umss-navy) !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
    }
    .fi-ta-ctn {
        border: 1px solid var(--umss-gray-200) !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.03) !important;
        background-color: var(--umss-white) !important;
        overflow: hidden !important;
    }
    .fi-ta-header-cell {
        background-color: var(--umss-gray-100) !important;
        color: var(--umss-gray-700) !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }
    .fi-section {
        border: 1px solid var(--umss-gray-200) !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03) !important;
        background-color: var(--umss-white) !important;
    }
</style>
