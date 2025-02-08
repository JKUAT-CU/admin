import requests
import pandas as pd

# Define the API endpoint
BASE_URL = "https://admin.jkuatcu.org/budgets.php"

# Define department IDs based on your database
departments = {
    "Admin": 1, "Podcast": 2, "Anzafyt": 3, "Nurturing": 4, "Finance": 5,
    "Ushering": 6, "Sound": 7, "EDIT": 8, "Publicity": 9, "Decor": 10,
    "Hospitality": 11, "Sports": 12, "Welfare": 13, "JMC": 14, "Sunday School": 15,
    "Missions": 16, "ET Committee": 17, "HSM": 18, "HCM": 19, "CSR": 20,
    "BS": 21, "Leadership Training": 22, "VukaFyt": 23, "Associates Committee": 24,
    "Hatua": 25, "OS Committee": 26, "Music Ministry": 27, "CREAM": 28,
    "Library Ministry": 29, "Prayer Committee": 30
}

# Set the semester
SEMESTER = "Fall 2023"  # Change if needed

# Initialize lists to store budget, event, and asset data
all_budgets = []
events_data = []
assets_data = []

# Loop through each department and fetch budget data
for dept_name, dept_id in departments.items():
    params = {"department_id": dept_id, "semester": SEMESTER}

    try:
        response = requests.get(BASE_URL, params=params)

        if response.status_code == 200:
            data = response.json()

            # Process budget data
            for budget in data.get("budgets", []):
                budget["department_name"] = dept_name
                all_budgets.append(budget)

                # Process events
                for event in budget.get("events", []):
                    for item in event.get("event_items", []):
                        events_data.append({
                            "department_name": dept_name,
                            "semester": budget["semester"],
                            "event_name": event.get("name"),
                            "attendance": event.get("attendance"),
                            "item_name": item.get("item_name"),
                            "item_quantity": item.get("item_quantity"),
                            "item_price": item.get("item_price"),
                            "item_total_cost": item.get("item_total_cost"),
                        })

                # Process assets
                for asset in budget.get("assets", []):
                    assets_data.append({
                        "department_name": dept_name,
                        "semester": budget["semester"],
                        "asset_name": asset.get("name"),
                        "quantity": asset.get("quantity"),
                        "price": asset.get("price"),
                        "total_cost": asset.get("quantity", 0) * asset.get("price", 0),
                    })
        else:
            print(f"Error {response.status_code}: Failed to fetch data for {dept_name}")

    except Exception as e:
        print(f"Exception occurred for {dept_name}: {e}")

# Convert lists to DataFrames
budgets_df = pd.DataFrame(all_budgets)
events_df = pd.DataFrame(events_data)
assets_df = pd.DataFrame(assets_data)

# Export to an Excel file with well-defined tables
with pd.ExcelWriter("budgets.xlsx", engine="xlsxwriter") as writer:
    budgets_df.to_excel(writer, sheet_name="Budgets", index=False)
    events_df.to_excel(writer, sheet_name="Events", index=False)
    assets_df.to_excel(writer, sheet_name="Assets", index=False)

print("✅ Budget data successfully exported to 'budgets.xlsx'")
