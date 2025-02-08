import requests
import pandas as pd

# API Endpoint
BASE_URL = "https://api.jkuatcu.org/excel.php"

# Department IDs
departments = {
    "Admin": 1, "Podcast": 2, "Anzafyt": 3, "Nurturing": 4, "Finance": 5,
    "Ushering": 6, "Sound": 7, "EDIT": 8, "Publicity": 9, "Decor": 10,
    "Hospitality": 11, "Sports": 12, "Welfare": 13, "JMC": 14, "Sunday School": 15,
    "Missions": 16, "ET Committee": 17, "HSM": 18, "HCM": 19, "CSR": 20,
    "BS": 21, "Leadership Training": 22, "VukaFyt": 23, "Associates Committee": 24,
    "Hatua": 25, "OS Committee": 26, "Music Ministry": 27, "CREAM": 28,
    "Library Ministry": 29, "Prayer Committee": 30
}

SEMESTER = "Fall 2023"  # Change as needed

# Data Storage
all_data = []

# Fetch and Process Data
for dept_name, dept_id in departments.items():
    params = {"department_id": dept_id, "semester": SEMESTER}

    try:
        response = requests.get(BASE_URL, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()

            for budget in data.get("budgets", []):
                for event in budget.get("events", []):
                    for item in event.get("event_items", []):
                        all_data.append([
                            dept_name, budget["semester"], budget["grand_total"], budget["status"],  # Budget Info
                            event.get("name"), event.get("attendance"), event.get("total_cost"),  # Event Info
                            item.get("item_name"), item.get("item_quantity"), item.get("item_price"), item.get("item_total_cost")  # Event Items
                        ])

                for asset in budget.get("assets", []):
                    all_data.append([
                        dept_name, budget["semester"], budget["grand_total"], budget["status"],  # Budget Info
                        None, None, None,  # Event Info (Empty for Assets)
                        asset.get("name"), asset.get("quantity"), asset.get("price"), asset.get("total_cost")  # Asset Info
                    ])

            print(f"✅ Successfully fetched data for {dept_name}")

        else:
            print(f"❌ Error {response.status_code}: Failed to fetch data for {dept_name}")

    except Exception as e:
        print(f"🚨 Exception for {dept_name}: {e}")

# Convert to DataFrame
columns = [
    "Department", "Semester", "Budget Total", "Budget Status",
    "Event Name", "Attendance", "Event Total Cost",
    "Item/Asset Name", "Quantity", "Price", "Total Cost"
]
df = pd.DataFrame(all_data, columns=columns)

# Check if DataFrame is empty before exporting
if df.empty:
    print("⚠️ No data retrieved. Excel file not created.")
else:
    # Write to Excel (Single Sheet, Multiple Tables)
    with pd.ExcelWriter("budgets.xlsx", engine="xlsxwriter") as writer:
        df.to_excel(writer, sheet_name="Budget Overview", index=False)

        # Formatting
        workbook = writer.book
        worksheet = writer.sheets["Budget Overview"]
        format_bold = workbook.add_format({"bold": True, "bg_color": "#D3D3D3"})
        worksheet.set_row(0, None, format_bold)

    print("📂 Data successfully exported to 'budgets.xlsx'")
