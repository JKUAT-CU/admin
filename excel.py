import requests
import openpyxl

# URL of the PHP API
API_URL = "https://jkuatcu.org/excel.php"  # Update with your actual API URL

# Fetch data from API
response = requests.get(API_URL)

if response.status_code != 200:
    print("Failed to fetch data:", response.status_code)
    exit()

data = response.json()

# Create a new Excel workbook
wb = openpyxl.Workbook()
ws = wb.active
ws.title = "Budgets"

# Define headers
headers = [
    "Department Name", "Semester", "Grand Total", "Finance Approved Total",
    "Asset Name", "Quantity", "Price",
    "Event Name", "Attendance", "Event Item", "Item Quantity", "Item Price"
]

# Add headers to sheet
ws.append(headers)

# Populate data rows
for budget in data:
    ws.append([
        budget['department_name'], budget['semester'], budget['grand_total'], budget['finance_approved_total'],
        budget['asset_name'], budget['asset_quantity'], budget['asset_price'],
        budget['event_name'], budget['attendance'],
        budget['event_item_name'], budget['event_item_quantity'], budget['event_item_price']
    ])

# Auto-adjust column widths
for col in ws.columns:
    max_length = 0
    col_letter = col[0].column_letter
    for cell in col:
        try:
            if cell.value:
                max_length = max(max_length, len(str(cell.value)))
        except:
            pass
    ws.column_dimensions[col_letter].width = max_length + 2

# Save Excel file
wb.save("Budgets.xlsx")
print("Excel file 'Budgets.xlsx' generated successfully.")
