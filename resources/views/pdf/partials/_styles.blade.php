<style>
    body {
        font-family: sans-serif;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    .invoice-header {
        text-align: center;
        margin-top: 20px;
        color: #000;
    }

    /* --- Header Layout Styles (No Borders) --- */
    .header-container {
        overflow: hidden;
        padding-bottom: 10px;
        margin-bottom: 30px;
        padding-top: 10px;
    }

    .left-header-block {
        float: left;
        width: 65%;
    }

    .team-logo {
        float: left;
        margin-right: 15px;
    }

    .team-details {
        overflow: hidden;
        padding-top: -6px;
        margin-left: 5rem;
    }

    .team-details p {
        margin: 0;
        line-height: 1.4;
        font-size: 14px;
    }

    .team-details p strong {
        font-size: 16px;
    }

    .right-header-block {
        float: right;
        width: 30%;
        text-align: right;
        font-size: 14px;
        font-weight: bold;
        margin-top: -1rem;
    }

    /* Clearfix utility */
    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }

    /* --- New & Improved Section Styling --- */
    .section-separator {
        border-bottom: 1px solid #eee;
        margin: 25px 0;
    }

    .details-group {
        display: table;
        width: 100%;
        margin-bottom: 4px;
    }

    .details-row {
        display: table-row;
    }

    .details-label,
    .details-value {
        display: table-cell;
        padding: 0px 0;
        vertical-align: top;
        font-size: 14px;
    }

    .details-label {
        width: 80px;
        font-weight: bold;
        color: #555;
    }

    .details-value {
        color: #333;
    }

    /* Specific styles for Client and Invoice Details blocks */
    .client-info,
    .invoice-info {
        background-color: #f9f9f9;
        border: 1px solid #eee;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .client-info .section-title,
    .invoice-info .section-title {
        margin-top: 0;
        margin-bottom: 0px;
        padding-bottom: 5px;
        border-bottom: 1px solid #ddd;
    }


    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        border: 1px solid #bdbdbd;
    }

    th,
    td {
        border: 1px solid #eee;
        padding: 4px 4px;
        text-align: left;
        font-size: 13px;
    }

    th {
        background-color: #bdbdbd;
        font-weight: bold;
        color: #2a2a2a;
        text-transform: uppercase;
    }

    tr:nth-child(even) {
        background-color: #fdfdfd;
    }

    .total {
        text-align: right;
        margin-top: 20px;
        font-size: 18px;
        padding: 10px 0;
        border-top: 2px solid #eee;
        margin-bottom: 20px;
    }

    .total strong {
        color: #000;
        font-size: 20px;
    }

    .user-details.text-footer {
        text-align: center;
        margin-top: 40px;
        font-size: 11px;
        color: #777;
    }
    .user-details.text-footer a {
        color: #ee7a15;
        text-decoration: none;
    }
    .user-details.text-footer a:hover {
        text-decoration: underline;
    }
</style>